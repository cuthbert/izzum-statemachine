<?php
namespace izzum\statemachine\utils;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Codeception\Attribute\Group;
use izzum\statemachine\utils\ExternalData;

/**
 * @author rolf
 *
 */
#[Group('statemachine', 'ExternalData')]
class ExternalDataTest extends TestCase {
    
    #[Test]
    public function shouldWorkAsExpectedViaPublicMethods()
    {
        //cleanup
        ExternalData::clear();
        
        $test_string = 'test';
        $test_array = ['test', 'test'];
        $this->assertFalse(ExternalData::has());
        $this->assertNull(ExternalData::get());
        
        ExternalData::set($test_string);
        $this->assertTrue(ExternalData::has());
        $this->assertEquals($test_string, ExternalData::get());
        $this->assertEquals($test_string, ExternalData::get(), 'call it twice, still has context');
        
        ExternalData::clear();
        $this->assertFalse(ExternalData::has());
        $this->assertNull(ExternalData::get());
        
        ExternalData::set($test_array);
        $this->assertTrue(ExternalData::has());
        $this->assertEquals($test_array, ExternalData::get());
        $this->assertEquals($test_array, ExternalData::get(), 'call it twice, still has context');
        
        //cleanup
        ExternalData::clear();
        
    }
    
}