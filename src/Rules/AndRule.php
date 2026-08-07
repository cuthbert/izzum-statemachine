<?php
namespace Izzum\Rules;

/**
 * When a rule is chained using the 'AND' operator both rules given in the
 * constructor should apply.
 *
 * @author Rolf Vreijdenberger
 * @author Richard Ruiter
 */
class AndRule extends Rule {
    /**
     *
     * @param Rule $original            
     * @param Rule $other            
     */
    public function __construct(private readonly Rule $original, private readonly Rule $other)
    {
    }

    protected function _applies()
    {
        return (bool) $this->original->applies() && $this->other->applies();
    }

    /**
     *
     * @return string
     */
    #[\Override]
    public function toString()
    {
        // includes the namespace
        $original = $this->original->toString();
        $other = $this->other->toString();
        return "($original and $other)";
    }

    /**
     * Merge results
     *
     * @return array
     */
    #[\Override]
    public function getResults()
    {
        return array_merge($this->other->getResults(), $this->original->getResults());
    }
}
