<?php
namespace Izzum\StateMachine;
use Izzum\StateMachine\Persistence\Adapter;
use Izzum\StateMachine\Persistence\Memory;

/**
 * Context is an object that holds all the contextual information for the
 * statemachine to do it's work with the help of the relevant dependencies.
 * A Context is created by your application to provide the right dependencies
 * ('context') for the statemachine to work with.
 * 
 * It seperates the concerns for the statemachine of how you are reading/writing 
 * state data and of how you access your domain models.
 *
 * Important are:
 * - the entity id, which references an application domain specific object
 * like 'Order' or 'Customer' that goes through some finite states in it's
 * lifecycle.
 * - the machine name, which is the type identifier for the machine and related
 * to the entity (eg: 'order-machine')
 * - persistence adapter, which reads/writes to/from a storage facility
 * - entity_builder, which constructs the stateful entity.
 *
 * The entity is the object that will be acted upon by the
 * Statemachine. This stateful object will be uniquely identified by it's id,
 * which will mostly be some sort of primary key for that object that is defined
 * by the application specific implementation.
 *
 * A reference to the stateful object can be obtained via the factory method
 * getEntity().
 *
 * This class delegates reading and writing states to specific implementations
 * of the Adapter classes. this is useful for
 * testing and creating specific behaviour for statemachines that need extra
 * functionality to get and set the correct states.
 * 
 *
 * @author Rolf Vreijdenberger
 *        
 */
class Context implements \Stringable {
    
    /**
     * the Identifier that uniquely identifies the statemachine
     *
     * @var Identifier
     */
    protected $identifier;
    
    /**
     * an associated statemachine, if one is set.
     * Only a statemachine that uses this Context should set itself on the
     * Context, providing a bidirectional association.
     *
     * @var StateMachine
     */
    protected $statemachine;

    /**
     * Constructor
     *
     * @param Identifier $identifier
     *            the identifier for the statemachine
     * @param EntityBuilder $entityBuilder
     *            optional: A specific builder class to create a reference to
     *            the entity we wish to manipulate/have access to.
     * @param Adapter $persistenceAdapter
     *            optional: A specific reader/writer class can be used to
     *            generate different 'read/write' behaviour
     */
    public function __construct(Identifier $identifier, /**
     * the builder to get the reference to the entity.
     */
    protected $entityBuilder = null, /**
     * the instance for getting to the persistence layer
     */
    protected $persistenceAdapter = null)
    {
        $this->identifier = $identifier;
    }

    /**
     * Provides a bidirectional association with the statemachine.
     * This method should be called only by the StateMachine itself.
     *
     * @param StateMachine $statemachine            
     */
    public function setStateMachine(StateMachine $statemachine)
    {
        $this->statemachine = $statemachine;
    }

    /**
     * gets the associated statemachine (if a statemachine is associated)
     *
     * @return StateMachine|null
     */
    public function getStateMachine()
    {
        return $this->statemachine;
    }

    /**
     * Gets a (cached) reference to the application domain specific model,
     * for example an 'Order' or 'Customer' that transitions through states in
     * it's lifecycle.
     *
     *
     * @param boolean $createFreshEntity
     *            optional
     * @return mixed
     */
    public function getEntity($createFreshEntity = false)
    {
        // use a specialized builder object to create the (cached) reference.
        return $this->getBuilder()->getEntity($this->getIdentifier(), $createFreshEntity);
    }

    /**
     * gets the state.
     * first try the backend storage facility. If not found, then try the
     * configured statemachine itself for the initial state.
     *
     * @return string
     */
    public function getState()
    {
        // get the state by delegating to a specific reader
        $state = $this->getPersistenceAdapter()->getState($this->getIdentifier());
        // if found
        if ($state !== State::STATE_UNKNOWN) {
            return $state;
        }
        // not found, try to get it from the states loaded on the statemachine
        if (!$this->getStateMachine()) {
            // reference to statemachine does not exist (possible standalone context object)
            return $state;
        }
        // reference to machine exists, just try to get the initial state
        $state = $this->getStateMachine()->getInitialState(true);
        if ($state === null) {
            return State::STATE_UNKNOWN;
        }
        // we have a State instance, get the name
        $state = $state->getName();
        return $state;
    }

    /**
     * Sets the state
     *
     * @param string $state 
     * @param string $message optional message. this can be used by the persistence adapter
     *          to be part of the transition history to provide extra information about the transition.            
     * @return boolan true if there was never any state persisted for this
     *         machine before (just added for the
     *         first time), false otherwise
     */
    public function setState($state, $message = null)
    {
        // set the state by delegating to a specific writer
        return $this->getPersistenceAdapter()->setState($this->getIdentifier(), $state, $message);
    }

    /**
     * adds the state data to the persistence layer if it is not there.
     * Used to mark the initial construction of a statemachine at a certain
     * point in time. subsequent calls to 'add' will not have any effect if it
     * has already been persisted.
     *
     * @param string $state
     * @param string $message optional message. this can be used by the persistence adapter
     *          to be part of the transition history to provide extra information about the transition.            
     * @return boolean true if it was added, false if it was already there
     */
    public function add($state, $message = null)
    {
        return $this->getPersistenceAdapter()->add($this->getIdentifier(), $state, $message);
    }

    /**
     * returns the builder used to get the application domain specific model.
     *
     * @return EntityBuilder
     */
    public function getBuilder()
    {
        if ($this->entityBuilder === null || !is_a($this->entityBuilder, 'Izzum\StateMachine\EntityBuilder')) {
            // the default builder returns the Identifier as the entity
            $this->entityBuilder = new EntityBuilder();
        }
        return $this->entityBuilder;
    }

    /**
     * gets the Context state reader/writer.
     *
     * @return Adapter a concrete persistence adapter
     */
    public function getPersistenceAdapter()
    {
        if ($this->persistenceAdapter === null || !is_a($this->persistenceAdapter, 'Izzum\StateMachine\Persistence\Adapter')) {
            // the default
            $this->persistenceAdapter = new Memory();
        }
        return $this->persistenceAdapter;
    }

    /**
     * gets the entity id that represents the unique identifier for the
     * application domain specific model.
     *
     * @return string
     */
    public function getEntityId()
    {
        return $this->getIdentifier()->getEntityId();
    }

    /**
     * get the Identifier
     *
     * @return Identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * gets the statemachine name that handles the entity
     *
     * @return string
     */
    public function getMachine()
    {
        return $this->getIdentifier()->getMachine();
    }

    /**
     * get the toString representation
     *
     * @return string
     */
    public function toString()
    {
        return static::class . "(" . $this->getId(true) . ")";
    }

    /**
     * get the unique identifier for an Context, which consists of the machine
     * name and the entity_id in parseable form, with an optional state
     *
     * @param boolean $readable
     *            human readable or not. defaults to false
     * @param boolean $withState
     *            append current state. defaults to false
     * @return string
     */
    public function getId($readable = false, $withState = false)
    {
        $output = $this->getIdentifier()->getId($readable);
        if ($readable) {
            if ($withState) {
                $output .= ", state: '" . $this->getState() . "'";
            }
        } else {
            if ($withState) {
                $output .= "_" . $this->getState();
            }
        }
        
        return $output;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * stores a failed transition, called by the statemachine
     * This is a transition that has failed since it:
     * - was not allowed
     * - where an exception was thrown from a rule or command
     * - etc. any general transition failure
     *
     * @param Transition $transition            
     * @param Exception $e            
     */
    public function setFailedTransition(Transition $transition, Exception $e)
    {
        $this->getPersistenceAdapter()->setFailedTransition($this->getIdentifier(), $transition, $e);
    }
}