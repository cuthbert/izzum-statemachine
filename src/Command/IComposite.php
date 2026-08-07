<?php
namespace Izzum\Command;

/**
 * The Interface for a CompositeCommand aka.
 * MacroCommand.
 * It extends ICommand and therefore functions as a command(with execute())
 * 
 * @author Rolf Vreijdenberger
 */
interface IComposite extends ICommand {

    /**
     * add a command to the composite, to be executed in the sequence of
     * commands
     * 
     * @param ICommand $command
     *            the ICommand to add
     */
    public function add(ICommand $command): void;

    /**
     * remove a command from the composite
     *
     * @param ICommand $command
     *            the ICommand to remove
     * @return bool whether or not the removal was succesful
     */
    public function remove(ICommand $command): bool;

    /**
     * checks if a certain command is present.
     *
     * @param ICommand $command
     *            The Icommand to check for.
     * @return bool true if the command is in the composite.
     */
    public function contains(ICommand $command): bool;

    /**
     * how many commands does this composite contain?\
     * 
     * @return int
     */
    public function count(): int;
}