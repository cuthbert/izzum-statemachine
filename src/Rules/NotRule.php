<?php
namespace Izzum\Rules;

/**
 * When a rule is chained using the 'NOT' operator the rule given in the
 * constructor should not apply.
 * The rule is effectively negated.
 *
 * @author Rolf Vreijdenberger
 * @author Richard Ruiter
 */
class NotRule extends Rule {
    
    /**
     *
     * @param Rule $original            
     */
    public function __construct(private readonly Rule $original)
    {
    }

    public function _applies()
    {
        return (boolean) !$this->original->applies();
    }

    /**
     * Return original results
     *
     * @return array
     */
    #[\Override]
    public function getResults()
    {
        return $this->original->getResults();
    }
}
