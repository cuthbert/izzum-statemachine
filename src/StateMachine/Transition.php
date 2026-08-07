<?php
namespace Izzum\StateMachine;
use Izzum\Rules\TrueRule;
use Izzum\StateMachine\Utils\Utils;
use Izzum\Rules\Rule;
use Izzum\Rules\AndRule;
use Izzum\Rules\Izzum\Rules;
use Izzum\Rules\IRule;

/**
 * Transition class
 * An abstraction for everything that is needed to make an allowed and succesful
 * transition between states.
 *
 * It has functionality to accept a Rule (guard logic) and a Command (transition
 * logic) as well as callables for the guard logic and transition logic .
 * callables are: closures, anonymous functions, user defined functions,
 * instance methods, static methods etc. see the php manual.
 *
 * The guards are used to check whether a transition can take place (Rule and callable)
 * The logic parts are used to execute the transition logic (Command and callable)
 *
 * Rules and commands should be able to be found/autoloaded by the application
 *
 * If transitions share the same states (both to and from) then they should
 * point to the same object reference (same states should share the exact same state
 * configuration).
 *
 * @link https://php.net/manual/en/language.types.callable.php
 * @link https://en.wikipedia.org/wiki/Command_pattern
 * @author Rolf Vreijdenberger
 *
 */
class Transition implements \Stringable {
    const RULE_TRUE = '\Izzum\Rules\TrueRule';
    const RULE_FALSE = '\Izzum\Rules\FalseRule';
    const RULE_EMPTY = '';
    const COMMAND_NULL = '\Izzum\Command\NullCommand';
    const COMMAND_EMPTY = '';
    const CALLABLE_NULL = null;
    const CALLABLE_GUARD = 'transition guard';
    const CALLABLE_TRANSITION = 'transition logic';

    /**
     * the state this transition starts from
     *
     * @var State
     */
    protected $stateFrom;

    /**
     * the state this transition points to
     *
     * @var State
     */
    protected $stateTo;

    /**
     * an event code that can trigger this transitions
     *
     * @var string
     */
    protected $event;

    /**
     * The fully qualified Rule class name of the
     * Rule to be applied to check if we can transition.
     * This can actually be a ',' seperated string of multiple rules.
     *
     * @var string
     */
    protected $rule;

    /**
     * the fully qualified Command class name of the Command to be
     * executed as part of the transition logic.
     * This can actually be a ',' seperated string of multiple commands.
     *
     * @var string
     */
    protected $command;

    /**
     * the callable to call as part of the transition logic
     * @var callable
     */
    protected $callableTransition;

    /**
     * the callable to call as part of the transition guard (should return a boolean)
     * @var callable
     */
    protected $callableGuard;

    /**
     * a description for the state
     *
     * @var string
     */
    protected $description;

    /**
     *
     * @param State $stateFrom
     * @param State $stateTo
     * @param string|null $event
     *            optional: an event name by which this transition can be
     *            triggered
     * @param string|null $rule
     *            optional: one or more fully qualified Rule (sub)class name(s)
     *            to check to see if we are allowed to transition.
     *            This can actually be a ',' seperated string of multiple rules
     *            that will be applied as a chained 'and' rule.
     * @param string|null $command
     *            optional: one or more fully qualified Command (sub)class
     *            name(s) to execute for a transition.
     *            This can actually be a ',' seperated string of multiple
     *            commands that will be executed as a composite.
     * @param callable|null $callableGuard
     *            optional: a php callable to call. eg: "function(){echo 'closure called';};"
     * @param callable|null $callableTransition
     *            optional: a php callable to call. eg: "Izzum\MyClass::myStaticMethod"
     */
    public function __construct(State $stateFrom, State $stateTo, $event = null, $rule = self::RULE_EMPTY, $command = self::COMMAND_EMPTY, $callableGuard = self::CALLABLE_NULL, $callableTransition = self::CALLABLE_NULL)
    {
        $this->stateFrom = $stateFrom;
        $this->stateTo = $stateTo;
        $this->setRuleName($rule);
        $this->setCommandName($command);
        $this->setGuardCallable($callableGuard);
        $this->setTransitionCallable($callableTransition);
        // setup bidirectional relationship with state this transition
        // originates from. only if it's not a regex or final state type
        if (!$stateFrom->isRegex() && !$stateFrom->isFinal()) {
            $stateFrom->addTransition($this);
        }
        // set and sanitize event name
        $this->setEvent($event);
    }

    /**
     * the callable to call as part of the transition logic
     * @param callable|null $callable
     */
    public function setTransitionCallable($callable) {
        $this->callableTransition = $callable;
        return $this;
    }

    /**
     * returns the callable for the transition logic.
     * @return callable or null
     */
    public function getTransitionCallable()
    {
        return $this->callableTransition;
    }

    /**
     * the callable to call as part of the transition guard
     * @param callable|null $callable
     */
    public function setGuardCallable($callable) {
        $this->callableGuard = $callable;
        return $this;
    }

    /**
     * returns the callable for the guard logic.
     * @return callable or null
     */
    public function getGuardCallable()
    {
        return $this->callableGuard;
    }

    /**
     * Can this transition be triggered by a certain event?
     * This also matches on the transition name.
     *
     * @param string|null $event not enforced by a native type, since callers
     *        may pass through arbitrary/unchecked event values
     * @return boolean
     */
    public function isTriggeredBy($event)
    {
        return $this->event === $event || $this->getName() === $event;
    }

    /**
     * is a transition possible? Check the guard Rule with the domain object
     * injected.
     *
     * @param Context $context
     * @return boolean
     */
    public function can(Context $context)
    {
        try {
            if(!$this->getRule($context)->applies()) {
                return false;
            }
            return $this->callCallable($this->getGuardCallable(), $context, self::CALLABLE_GUARD);
        } catch(\Exception $e) {
            //rule or callable failure
            $e = new Exception($this->toString() . ' '. $e->getMessage(), Exception::RULE_APPLY_FAILURE, $e);
            throw $e;
        }
    }

    /**
     * Process the transition for the statemachine and execute the associated
     * Command with the domain object injected.
     *
     * @param Context $context
     * @return void
     */
    public function process(Context $context)
    {
        // execute, we do not need to check if we 'can' since this is done
        // by the statemachine itself
        try {
            $this->getCommand($context)->execute();
            $this->callCallable($this->getTransitionCallable(), $context, self::CALLABLE_TRANSITION);
        } catch(\Exception $e) {
            // command or callable failure
            $e = new Exception($e->getMessage(), Exception::COMMAND_EXECUTION_FAILURE, $e);
            throw $e;
        }
    }

    /**
     * calls the $callable as part of the transition
     * @param callable $callable
     * @param Context $context
     * @throws Exception in case of an invalid callable
     */
    protected function callCallable($callable, Context $context, $type = 'n/a') {
        //in case it is a guard callable we need to return true/false
        if($callable != self::CALLABLE_NULL){
            Utils::checkCallable($callable, $type, "transition: " . $this, $context);
            return (bool) call_user_func($callable, $context->getEntity());
        }
        return true;
    }

    /**
     * returns the associated Rule for this Transition,
     * configured with a 'reference' (stateful) object
     *
     * @param Context $context
     *            the associated Context for a our statemachine
     * @return IRule a Rule or chained AndRule if the rule input was a ','
     *         seperated string of rules.
     * @throws Exception
     */
    public function getRule(Context $context)
    {
        // if no rule is defined, just allow the transition by default
        if ($this->rule === '' || $this->rule === null) {
            return new TrueRule();
        }

        $entity = $context->getEntity();

        // a rule string can be made up of multiple rules seperated by a comma
        $allRules = explode(',', $this->rule);
        $rule = new TrueRule();
        foreach ($allRules as $singleRule) {

            // guard clause to check if rule exists
            if (!class_exists($singleRule)) {
                $e = new Exception(sprintf("failed rule creation, class does not exist: (%s) for Context (%s).", $this->rule, $context->toString()), Exception::RULE_CREATION_FAILURE);
                throw $e;
            }

            try {
                $andRule = new $singleRule($entity);
                // create a chain of rules that need to be true
                $rule = new AndRule($rule, $andRule);
            } catch(\Exception $e) {
                $e = new Exception(sprintf("failed rule creation, class objects to construction with entity: (%s) for Context (%s). message: %s", $this->rule, $context->toString(), $e->getMessage()), Exception::RULE_CREATION_FAILURE);
                throw $e;
            }
        }
        return $rule;
    }


    /**
     * returns the associated Command for this Transition.
     * the Command will be configured with the 'reference' of the stateful
     * object.
     * In case there have been multiple commands as input (',' seperated), this
     * method will return a Composite command.
     *
     * @param Context $context
     * @return \Izzum\Command\ICommand
     * @throws Exception
     */
    public function getCommand(Context $context)
    {
        return Utils::getCommand($this->command, $context);
    }

    /**
     *
     * @return string
     */
    public function toString()
    {
        return static::class . " '" . $this->getName() . "' [event]: '" . $this->event . "'" . " [rule]: '" . $this->rule . "' [command]: '" . $this->command . "'";
    }

    /**
     * get the state this transition points from
     *
     * @return State
     */
    public function getStateFrom()
    {
        return $this->stateFrom;
    }

    /**
     * get the state this transition points to
     *
     * @return State
     */
    public function getStateTo()
    {
        return $this->stateTo;
    }

    /**
     * get the transition name.
     * the transition name is always unique for a statemachine
     * since it constists of <state_from>_to_<state_to>
     *
     * @return string
     */
    public function getName()
    {
        $name = Utils::getTransitionName($this->getStateFrom()->getName(), $this->getStateTo()->getName());
        return $name;
    }

    /**
     * return the command name(s).
     * one or more fully qualified command (sub)class name(s) to execute for a
     * transition.
     * This can actually be a ',' seperated string of multiple commands that
     * will be executed as a composite.
     *
     * @return string
     */
    public function getCommandName()
    {
        return $this->command;
    }

    /**
     * @param string|null $command null is treated the same as self::COMMAND_EMPTY
     */
    public function setCommandName($command)
    {
        $this->command = trim($command ?? self::COMMAND_EMPTY);
        return $this;
    }

    public function getRuleName()
    {
        return $this->rule;
    }

    /**
     * @param string|null $rule null is treated the same as self::RULE_EMPTY
     */
    public function setRuleName($rule)
    {
        $this->rule = trim($rule ?? self::RULE_EMPTY);
        return $this;
    }

    /**
     * set the description of the transition (for uml generation for example)
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * get the description for this transition (if any)
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * set the event name by which this transition can be triggered.
     * In case the event name is null or an empty string, it defaults to the
     * transition name.
     *
     * @param string|null $event
     */
    public function setEvent($event)
    {
        if ($event === null || $event === '') {
            $event = $this->getName();
        }
        $this->event = $event;
        return $this;
    }

    /**
     * get the event name by which this transition can be triggered
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * for transitions that contain regex states, we need to be able to copy an
     * existing (subclass of this) transition with all it's fields.
     * We need to instantiate it with a different from and to state since either
     * one of those states can be the regex states. All other fields need to be
     * copied.
     *
     * Override this method in a subclass to add other fields. By using 'new
     * static' we are already instantiating a possible subclass.
     *
     *
     * @param State $from
     * @param State $to
     * @return Transition
     */
    public function getCopy(State $from, State $to)
    {
        // @phpstan-ignore new.static (intentional late static binding so subclasses are copied as their own type, per docblock above)
        $copy = new static($from, $to, $this->getEvent(), $this->getRuleName(), $this->getCommandName(), $this->getGuardCallable(), $this->getTransitionCallable());
        $copy->setDescription($this->getDescription());
        return $copy;
    }

    /**
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}