<?php
/**
 * Created by PhpStorm.
 * User: theus
 * Date: 15/09/16
 * Time: 11:17
 */

namespace Tests\AppBundle\Entity;


use AppBundle\Entity\I18n;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\DateTime;

class I18nTest extends TestCase
{

    /** @var I18n */
    protected $data;
    protected function setUp()
    {
        parent::setUp();
        $this->data = new I18n();
    }


    public function testAllFieldsNull()
    {
        $this->assertNull($this->data->getContent());
        $this->assertNull($this->data->getCreated());
        $this->assertNull($this->data->getId());
        $this->assertNull($this->data->getIdentifier());
        $this->assertNull($this->data->getLocale());
        $this->assertNull($this->data->getModified());
    }



    public function testSetFields()
    {
        $now = new DateTime();
        $this->assertEquals('sslkdnn', $this->data->setContent('sslkdnn')->getContent());
        $this->assertEquals('fr', $this->data->setLocale('fr')->getLocale());
        $this->assertEquals($now, $this->data->setModified($now)->getModified());
    }
}