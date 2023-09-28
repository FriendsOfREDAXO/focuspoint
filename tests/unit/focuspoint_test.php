<?php

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class focuspoint_test extends TestCase
{
    public function testMetaInfoType()
    {
        $sql = rex_sql::factory();
        $sql->prepareQuery('SELECT * FROM rex_metainfo_type WHERE Label =:label');
        $sql->execute(['label'=>'Focuspoint (AddOn)']);

        $dbtype = $sql->getValue('dbtype');
        $length = $sql->getValue('dblength');
        $count = count($sql->getArray());

        //check if entry exists once
        static::assertFalse($count < 1, 'Meta Type "Focuspoint (AddOn)" not found');
        static::assertFalse($count > 1, 'Meta Type "Focuspoint (AddOn)" duplicate entry');
        //check db type and length
        static::assertEquals('varchar', $dbtype, 'Meta Type "Focuspoint (AddOn)" wrong dbtype');
        static::assertEquals(20, $length, 'Meta Type "Focuspoint (AddOn)" dblenght not 20');
    }
}
