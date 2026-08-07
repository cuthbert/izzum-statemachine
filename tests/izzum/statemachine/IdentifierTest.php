<?php
namespace izzum\statemachine;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Codeception\Attribute\Group;
use izzum\statemachine\persistence\Memory;

/**
 * 
 * @author rolf
 *        
 */
#[Group('statemachine', 'Context')]
class IdentifierTest extends TestCase {

    #[Test]
    public function shouldBehave()
    {
        $entity_id = "123";
        $machine = "test";
        $identifier = new Identifier($entity_id, $machine);
        $this->assertEquals($entity_id, $identifier->getEntityId());
        $this->assertEquals($machine, $identifier->getMachine());
        
        //getId
        $this->assertStringContainsString('test', $identifier->getId(true));
        $this->assertStringContainsString('test', $identifier->getId(false));
        $this->assertStringContainsString($entity_id, $identifier->getId(false));
        $this->assertStringContainsString($entity_id, $identifier->getId(true));
        $this->assertStringContainsString('machine', $identifier->getId(true));
        $this->assertStringContainsString('id', $identifier->getId(true));
        $this->assertStringNotContainsString('machine', $identifier->getId(false));
        $this->assertStringNotContainsString('id', $identifier->getId(false));

        //string representation
        $this->assertStringContainsString($entity_id, $identifier->toString());
        $this->assertStringContainsString($machine, $identifier->toString());
        $this->assertStringContainsString('Identifier', $identifier->toString());
        //__toString
        $this->assertStringContainsString($entity_id, $identifier . "");
        $this->assertStringContainsString($machine, $identifier . "");
        $this->assertStringContainsString('Identifier', $identifier . "");
        
        
        $identifier->setEntityId('321');
        $this->assertEquals('321', $identifier->getEntityId());
        
        
    }
}