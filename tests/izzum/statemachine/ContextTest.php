<?php
namespace izzum\statemachine;
use PHPUnit\Framework\TestCase;
use Codeception\Attribute\Group;
use izzum\statemachine\persistence\Memory;

/**
 * 
 * @author rolf
 *        
 */
#[Group('statemachine', 'Context')]
class ContextTest extends TestCase {

    /**
     * test the factory method with default parameters only
     * implicitely tests the constructor
     */
    public function testFactoryDefault()
    {
        $entity_id = "id123";
        $machine = "test-machine";
        $identifier = new Identifier($entity_id, $machine);
        
        // only mandatory parameters
        $o = new Context($identifier);
        $this->assertStringContainsString($entity_id, $o->getId());
        $this->assertStringContainsString($machine, $o->getId());
        $this->assertStringContainsString($entity_id, $o->getId(false));
        $this->assertStringContainsString($machine, $o->getId(false));
        $this->assertStringContainsString($entity_id, $o->getId(true));
        $this->assertStringContainsString($machine, $o->getId(true));
        
        $this->assertEquals($entity_id, $o->getEntityId());
        $this->assertEquals($machine, $o->getMachine());
        $this->assertNull($o->getStateMachine());
        // defaulting to database readers and writers
        $this->assertTrue(is_a($o->getPersistenceAdapter(), 'izzum\statemachine\persistence\Memory'));
        $this->assertTrue(is_a($o->getBuilder(), 'izzum\statemachine\EntityBuilder'));
        $this->assertEquals($o->getIdentifier(), $o->getEntity());

        $this->assertEquals($o->getIdentifier(), $identifier);

        $this->assertStringContainsString($entity_id, $o->toString());
        $this->assertStringContainsString($machine, $o->toString());
        $this->assertStringContainsString('izzum\statemachine\Context', $o->toString());
        
        $this->assertEquals(State::STATE_UNKNOWN, $o->getState());
    }

    public function testConversionOfContextIdToString()
    {
        $entity_id = 1;
        $machine = 'test';
        $identifier = new Identifier($entity_id, $machine);
        $o = new Context($identifier);
        $this->assertEquals($entity_id, $o->getEntityId());
        $this->assertEquals("1", $o->getEntityId());
    }

    public function testFull()
    {
        $entity_id = "id";
        $machine = "test machine";
        $identifier = new Identifier($entity_id, $machine);
        $builder = new EntityBuilder();
        $io = new Memory();
        
        // all parameters
        $o = new Context($identifier, $builder, $io);
        $this->assertEquals($entity_id, $o->getEntityId());
        $this->assertEquals($machine, $o->getMachine());
        $this->assertNull($o->getStateMachine());
        $this->assertTrue(is_a($o->getPersistenceAdapter(), 'izzum\statemachine\persistence\Memory'));
        $this->assertTrue(is_a($o->getBuilder(), 'izzum\statemachine\EntityBuilder'));
        $this->assertEquals($o->getIdentifier(), $o->getEntity());

        $this->assertStringContainsString($entity_id, $o->toString());
        $this->assertStringContainsString($machine, $o->toString());
        $this->assertStringContainsString('izzum\statemachine\Context', $o->toString());
        
        // even though we have a valid reader, the state machine does not exist.
        $this->assertEquals(State::STATE_UNKNOWN, $o->getState());
        $this->assertTrue($o->setState('lala'));
        $this->assertEquals('lala', $o->getState());
        
        // adding
        $machine = 'add-experiment-machine';
        $context = new Context(new Identifier('add-experiment-id', $machine), $builder, $io);
        $sm = new StateMachine($context);
        $sm->addTransition(new Transition(new State('c', State::TYPE_FINAL), new State('d'), State::TYPE_NORMAL));
        $sm->addTransition(new Transition(new State('a', State::TYPE_INITIAL), new State('b')));
        $this->assertCount(0, $context->getPersistenceAdapter()->getEntityIds($machine));
        $state = $sm->getInitialState()->getName();
        $this->assertEquals('a', $state);
        $this->assertTrue($context->add($state));
        // var_dump( Memory::get());
        $this->assertCount(1, $context->getPersistenceAdapter()->getEntityIds($machine));
    }

    /**
     * test the factory method with all parameters provided
     * implicitely tests the constructor
     */
    public function testContext()
    {
        $entity_id = "id1";
        $machine = "test machine";
        $identifier = new Identifier($entity_id, $machine);
        $builder = new EntityBuilder();
        $io = new Memory();
        
        // all parameters
        $o = new Context($identifier, $builder, $io);
        $this->assertEquals($entity_id, $o->getEntityId());
        $this->assertEquals($machine, $o->getMachine());
        $this->assertNull($o->getStateMachine());
        $this->assertTrue(is_a($o->getPersistenceAdapter(), 'izzum\statemachine\persistence\Memory'));
        $this->assertTrue(is_a($o->getBuilder(), 'izzum\statemachine\EntityBuilder'));
        $this->assertEquals($identifier, $o->getEntity());

        $this->assertStringContainsString($entity_id, $o->toString());
        $this->assertStringContainsString($machine, $o->toString());
        $this->assertStringContainsString('izzum\statemachine\Context', $o->toString());
        
        // even though we have a valid reader, the state machine does not exist.
        $this->assertEquals(State::STATE_UNKNOWN, $o->getState());
        $this->assertTrue($o->setState(State::STATE_NEW, 'this is an informational message about why we set this state: we set this state to new for a unittest'), 'added');
        $this->assertFalse($o->setState(State::STATE_NEW), 'already there');
        
        // for coverage.
        $statemachine = new StateMachine($o);
        $this->assertNull($o->setStateMachine($statemachine));
        $this->assertStringContainsString('Context', $o . '', '__toString()');
    }
}