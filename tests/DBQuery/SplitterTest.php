<?php

require_once 'PHPUnit/Framework/TestCase.php';

require_once __DIR__ . '/../../src/DBQuery.php';
require_once __DIR__ . '/../../src/DBQuery/Splitter.php';

/**
 * Test for DBQuery_Splitter.
 * 
 * @package Test
 * @subpackage DBQuery
 */
class DBQuery_SplitterTest extends PHPUnit_Framework_TestCase
{

    /**
     * Helper function to remove spaces from a query.
     */
    static function cleanQuery($sql)
    {
        return trim(preg_replace('/(?:\s+(\s|\)|\,)|(\()\s+)/', '\1\2', $sql));
    }

    //--------

    public function testQuote_Null()
    {
        $this->assertEquals('NULL', DBQuery_Splitter::quote(null));
    }

    public function testQuote_NullDefault()
    {
        $this->assertEquals('DEFAULT', DBQuery_Splitter::quote(null, 'DEFAULT'));
    }

    public function testQuote_Int()
    {
        $this->assertEquals('1', DBQuery_Splitter::quote(1));
    }

    public function testQuote_Float()
    {
        $this->assertEquals('1.3', DBQuery_Splitter::quote(1.3));
    }

    public function testQuote_True()
    {
        $this->assertEquals('TRUE', DBQuery_Splitter::quote(true));
    }

    public function testQuote_False()
    {
        $this->assertEquals('FALSE', DBQuery_Splitter::quote(false));
    }

    public function testQuote_String()
    {
        $this->assertEquals('"test"', DBQuery_Splitter::quote('test'));
    }

    public function testQuote_StringQuotes()
    {
        $this->assertEquals('"test \"abc\" test"', DBQuery_Splitter::quote('test "abc" test'));
    }

    public function testQuote_StringMultiline()
    {
        $this->assertEquals('"line1\nline2\nline3"', DBQuery_Splitter::quote("line1\nline2\nline3"));
    }

    public function testQuote_Array()
    {
        $this->assertEquals('(1, TRUE, "abc", DEFAULT)', DBQuery_Splitter::quote(array(1, true, "abc", null), 'DEFAULT'));
    }

    public function testbackquote_Simple()
    {
        $this->assertEquals('`test`', DBQuery_Splitter::backquote("test"));
    }

    public function testbackquote_Quoted()
    {
        $this->assertEquals('`test`', DBQuery_Splitter::backquote("`test`"));
    }

    public function testbackquote_TableColumn()
    {
        $this->assertEquals('`abc`.`test`', DBQuery_Splitter::backquote("abc.test"));
    }

    public function testbackquote_TableColumn_Quoted()
    {
        $this->assertEquals('`abc`.`test`', DBQuery_Splitter::backquote("`abc`.`test`"));
    }

    public function testbackquote_WithAlias()
    {
        $this->assertEquals('`abc`.`test` AS `def`', DBQuery_Splitter::backquote("abc.test AS def"));
    }

    public function testbackquote_Function()
    {
        $this->assertEquals('count(`abc`.`test`) AS `count`', DBQuery_Splitter::backquote("count(abc.test) AS count"));
    }

    public function testbackquote_Cast()
    {
        $this->assertEquals('`qqq`, cast(`abc`.`test` AS DATETIME)', DBQuery_Splitter::backquote("qqq, cast(`abc`.test AS DATETIME)"));
    }

    public function testbackquote_Cast_Confuse()
    {
        $this->assertEquals('`qqq`, cast(myfn(`abc`.`test` as `myarg`) AS DATETIME) AS `date`', DBQuery_Splitter::backquote("qqq, cast(myfn(`abc`.test as myarg) AS DATETIME) AS date"));
    }

    public function testbackquote_Expression()
    {
        $this->assertEquals('`abc`.`test` - `def`.`total`*10 AS `grandtotal`', DBQuery_Splitter::backquote("abc.test - def.total*10 AS grandtotal"));
    }

    public function testbackquote_None()
    {
        $this->assertEquals('abc', DBQuery_Splitter::backquote("abc", DBQuery::BACKQUOTE_NONE));
    }

    public function testbackquote_Strict()
    {
        $this->assertEquals('`abd-def*10`', DBQuery_Splitter::backquote("abd-def*10", DBQuery::BACKQUOTE_STRICT));
    }

    public function testbackquote_Strict_TableColumn()
    {
        $this->assertEquals('`abc`.`test-10`', DBQuery_Splitter::backquote("`abc`.test-10", DBQuery::BACKQUOTE_STRICT));
    }

    public function testbackquote_Strict_Fail()
    {
        $this->setExpectedException('Exception', "Unable to quote '`abc`.`test`-10' safely");
        DBQuery_Splitter::backquote("`abc`.`test`-10", DBQuery::BACKQUOTE_STRICT);
    }

    public function testIsIdentifier_Simple()
    {
        $this->assertTrue(DBQuery_Splitter::isIdentifier('test'));
    }

    public function testIsIdentifier_Quoted()
    {
        $this->assertTrue(DBQuery_Splitter::isIdentifier('`test`'));
    }

    public function testIsIdentifier_TableColumn()
    {
        $this->assertTrue(DBQuery_Splitter::isIdentifier('abc.test'));
    }

    public function testIsIdentifier_TableColumn_Quoted()
    {
        $this->assertTrue(DBQuery_Splitter::isIdentifier('`abc`.`test`'));
    }

    public function testIsIdentifier_Strange()
    {
        $this->assertFalse(DBQuery_Splitter::isIdentifier('ta-$38.934#34@dhy'));
    }

    public function testIsIdentifier_Strange_Quoted()
    {
        $this->assertTrue(DBQuery_Splitter::isIdentifier('`ta-$38.934#34@dhy`'));
    }

    public function testIsIdientifier_WithoutAlias_AsAlias()
    {
        $this->assertFalse(DBQuery_Splitter::isIdentifier('`test` AS def'));
    }

    public function testIsIdentifier_WithoutAlias_SpaceAlias()
    {
        $this->assertFalse(DBQuery_Splitter::isIdentifier('`test` def'));
    }

    //--------


    public function testBind_Null()
    {
        $this->assertEquals('UPDATE phpunit_test SET description=NULL', DBQuery_Splitter::bind('UPDATE phpunit_test SET description=?', array(null)));
    }

    public function testBind_Integer()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE status=10', DBQuery_Splitter::bind("SELECT * FROM phpunit_test WHERE status=?", array(10)));
    }

    public function testBind_Float()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE status=33.7', DBQuery_Splitter::bind("SELECT * FROM phpunit_test WHERE status=?", array(33.7)));
    }

    public function testBind_Boolean()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE status=TRUE AND disabled=FALSE', DBQuery_Splitter::bind("SELECT * FROM phpunit_test WHERE status=? AND disabled=?", array(true, false)));
    }

    public function testBind_String()
    {
        $this->assertEquals('SELECT id, "test" AS `desc` FROM phpunit_test WHERE status="ACTIVE"', DBQuery_Splitter::bind('SELECT id, ? AS `desc` FROM phpunit_test WHERE status=?', array('test', 'ACTIVE')));
    }

    public function testBind_String_Confuse()
    {
        $this->assertEquals('SELECT id, "?" AS `desc ?`, \'?\' AS x FROM phpunit_test WHERE status="ACTIVE"', DBQuery_Splitter::bind('SELECT id, "?" AS `desc ?`, \'?\' AS x FROM phpunit_test WHERE status=?', array('ACTIVE', 'not me', 'not me', 'not me')));
    }

    public function testBind_String_Quote()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE description="This is a \\"test\\""', DBQuery_Splitter::bind('SELECT * FROM phpunit_test WHERE description=?', array('This is a "test"')));
    }

    public function testBind_String_Multiline()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE description="This is a \\"test\\"\\nWith another line"', DBQuery_Splitter::bind('SELECT * FROM phpunit_test WHERE description=?', array('This is a "test"' . "\n" . 'With another line')));
    }

    public function testBind_Array()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE description IN ("test", 10, FALSE, "another test")', DBQuery_Splitter::bind('SELECT * FROM phpunit_test WHERE description IN ?', array(array("test", 10, FALSE, "another test"))));
    }

    public function testBind_Named()
    {
        $this->assertEquals('SELECT id, "test" AS `desc` FROM phpunit_test WHERE status="ACTIVE"', DBQuery_Splitter::bind('SELECT id, :desc AS `desc` FROM phpunit_test WHERE status=:status', array('desc' => 'test', 'status' => 'ACTIVE')));
    }

    public function testBind_Like()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE description LIKE "%foo%"', DBQuery_Splitter::bind('SELECT * FROM phpunit_test WHERE description LIKE %?%', array('foo')));
    }

    public function testBind_Like_Named()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE description LIKE "%foo%"', DBQuery_Splitter::bind('SELECT * FROM phpunit_test WHERE description LIKE %:desc%', array('desc' => 'foo')));
    }

    public function testCountPlaceholders()
    {
        $this->assertEquals(3, DBQuery_Splitter::countPlaceholders('SELECT id, ? AS `desc`, :named AS `named` FROM phpunit_test WHERE status=?'));
    }

    public function testCountPlaceholders_Confuse()
    {
        $this->assertEquals(1, DBQuery_Splitter::countPlaceholders('SELECT id, "?" AS `:desc ?`, \'?\' AS x FROM phpunit_test WHERE status=?'));
    }

    //--------


    public function testGetQueryType_Select()
    {
        $this->assertEquals('SELECT', DBQuery_Splitter::getQueryType("SELECT id, description FROM `test`"));
    }

    public function testGetQueryType_Select_Word()
    {
        $this->assertEquals('SELECT', DBQuery_Splitter::getQueryType("SELECT"));
    }

    public function testGetQueryType_Select_LowerCase()
    {
        $this->assertEquals('SELECT', DBQuery_Splitter::getQueryType("select id, description from `test`"));
    }

    public function testGetQueryType_Select_Spaces()
    {
        $this->assertEquals('SELECT', DBQuery_Splitter::getQueryType("\n\t\n  SELECT id, description FROM `test`"));
    }

    public function testGetQueryType_Insert()
    {
        $this->assertEquals('INSERT', DBQuery_Splitter::getQueryType("INSERT INTO `test` SELECT 10"));
    }

    public function testGetQueryType_Replace()
    {
        $this->assertEquals('REPLACE', DBQuery_Splitter::getQueryType("REPLACE INTO `test` VALUES (10, 'UPDATE')"));
    }

    public function testGetQueryType_Delete()
    {
        $this->assertEquals('DELETE', DBQuery_Splitter::getQueryType("DELETE FROM `test` WHERE `select`=10"));
    }

    public function testGetQueryType_Truncate()
    {
        $this->assertEquals('TRUNCATE', DBQuery_Splitter::getQueryType("TRUNCATE `test`"));
    }

    public function testGetQueryType_AlterTable()
    {
        $this->assertEquals('ALTER TABLE', DBQuery_Splitter::getQueryType("ALTER TABLE `test`"));
    }

    public function testGetQueryType_AlterView_Spaces()
    {
        $this->assertEquals('ALTER VIEW', DBQuery_Splitter::getQueryType("ALTER\n\t\tVIEW `test`"));
    }

    public function testGetQueryType_AlterUnknown()
    {
        $this->assertNull(DBQuery_Splitter::getQueryType("ALTER test set abc"));
    }

    public function testGetQueryType_Set()
    {
        $this->assertEquals('SET', DBQuery_Splitter::getQueryType("SET @select=10"));
    }

    public function testGetQueryType_Begin()
    {
        $this->assertEquals('START TRANSACTION', DBQuery_Splitter::getQueryType("BEGIN"));
    }

    public function testGetQueryType_LoadDataInfile()
    {
        $this->assertEquals('LOAD DATA INFILE', DBQuery_Splitter::getQueryType("LOAD DATA INFILE"));
    }

    public function testGetQueryType_Comment()
    {
        $this->assertNull(DBQuery_Splitter::getQueryType("-- SELECT `test`"));
    }

    public function testGetQueryType_Unknown()
    {
        $this->assertNull(DBQuery_Splitter::getQueryType("something"));
    }

    //--------


    public function testAddParts_Simple()
    {
        $parts = array('select' => '', 'columns' => '*', 'from' => 'foo', 'where' => '', 'group by' => '', 'having' => '', 'order by' => '', 'limit' => '', 'options' => '');
        $parts = DBQuery_Splitter::addParts($parts, array('where' => array(DBQuery::APPEND => array('abc = 10'))));

        $expect = array('where' => 'abc = 10') + $parts;
        $this->assertEquals(array_map('trim', $expect), array_map('trim', $parts));
    }

    public function testAddParts_Split()
    {
        $parts = DBQuery_Splitter::addParts('SELECT * FROM foo', array('where' => array(DBQuery::APPEND => array('abc = 10'))));
        $expect = DBQuery_Splitter::split('SELECT * FROM foo WHERE abc = 10');
        $this->assertEquals(array_map('trim', $expect), array_map('trim', $parts));
    }

    public function testAddParts_Append()
    {
        $add = array(
            'from' => array(DBQuery::APPEND => array('INNER JOIN bar ON foo.id=bar.foo_id', 'LEFT JOIN pan ON foo.id=pan.foo_id')),
            'columns' => array(DBQuery::APPEND => array('abc, def', 'xyz')),
            'limit' => array(DBQuery::APPEND => array('10 OFFSET 50')),
            'where' => array(DBQuery::APPEND => array('abc = 10', 'xyz = 30')),
            'having' => array(DBQuery::APPEND => array('count(*) > 2')),
            'group by' => array(DBQuery::APPEND => array('def')),
            'order by' => array(DBQuery::APPEND => array('pan.type', 'def')),
        );
        $parts = DBQuery_Splitter::addParts('SELECT id FROM foo', $add);

        $expect = DBQuery_Splitter::split('SELECT id, abc, def, xyz FROM foo INNER JOIN bar ON foo.id=bar.foo_id LEFT JOIN pan ON foo.id=pan.foo_id WHERE (abc = 10) AND (xyz = 30) GROUP BY def HAVING count(*) > 2 ORDER BY pan.type, def LIMIT 10 OFFSET 50');
        $this->assertEquals(array_map('trim', $expect), array_map('trim', $parts));
    }

    public function testAddParts_Full()
    {
        $add = array(
            'from' => array(DBQuery::PREPEND => array('pan'), DBQuery::APPEND => array('LEFT JOIN ON pan.foo_id=foo.id')),
            'columns' => array(DBQuery::PREPEND => array('abc, def'), DBQuery::APPEND => array('xyz')),
            'where' => array(DBQuery::PREPEND => array('abc = 10'), DBQuery::APPEND => array('xyz = 30')),
            'having' => array(DBQuery::PREPEND => array('count(*) > 2')),
            'group by' => array(DBQuery::PREPEND => array('def')),
            'order by' => array(DBQuery::PREPEND => array('pan.type'), DBQuery::APPEND => array('def')),
        );
        $parts = DBQuery_Splitter::addParts('SELECT id FROM foo INNER JOIN bar ON foo.id=bar.foo_id WHERE foo.id > 30 GROUP BY bar.type HAVING x=y ORDER BY foo.id', $add);

        $expect = DBQuery_Splitter::split('SELECT abc, def, id, xyz FROM pan (foo INNER JOIN bar ON foo.id=bar.foo_id) LEFT JOIN ON pan.foo_id=foo.id WHERE (abc = 10) AND (foo.id > 30) AND (xyz = 30) GROUP BY def, bar.type HAVING (count(*) > 2) AND (x=y) ORDER BY pan.type, foo.id, def');
        $this->assertEquals(array_map('trim', $expect), array_map('trim', $parts));
    }

    public function testAddParts_Insert()
    {
        $add = array('values' => array(DBQuery::APPEND => array('NULL, 100, "a"', 'NULL, 100, "b"')));
        $parts = DBQuery_Splitter::addParts('INSERT INTO foo VALUES (NULL, 20, "test")', $add);

        $expect = DBQuery_Splitter::split('INSERT INTO foo VALUES (NULL, 20, "test"), (NULL, 100, "a"), (NULL, 100, "b")');
        $this->assertEquals(array_map('trim', $expect), array_map('trim', $parts));
    }

    public function testBuildWhere_Simple()
    {
        $where = DBQuery_Splitter::buildWhere('foo', 10);
        $this->assertEquals("`foo` = 10", $where);
    }

    public function testBuildWhere_MoreThan()
    {
        $where = DBQuery_Splitter::buildWhere('foo > ?', 10);
        $this->assertEquals("`foo` > 10", $where);
    }

    public function testBuildWhere_IsNull()
    {
        $where = DBQuery_Splitter::buildWhere('foo IS NULL');
        $this->assertEquals("`foo` IS NULL", $where);
    }

    public function testBuildWhere_In()
    {
        $where = DBQuery_Splitter::buildWhere('foo', array(10, 20));
        $this->assertEquals("`foo` IN (10, 20)", $where);
    }

    public function testBuildWhere_Between()
    {
        $where = DBQuery_Splitter::buildWhere('foo BETWEEN ? AND ?', array(10, 20));
        $this->assertEquals("`foo` BETWEEN 10 AND 20", $where);
    }

    public function testBuildWhere_Like()
    {
        $where = DBQuery_Splitter::buildWhere('bar LIKE %?%', "blue");
        $this->assertEquals('`bar` LIKE "%blue%"', $where);
    }

    public function testBuildWhere_TwoParams()
    {
        $where = DBQuery_Splitter::buildWhere('foo = ? AND bar LIKE %?%', array(10, "blue"));
        $this->assertEquals('`foo` = 10 AND `bar` LIKE "%blue%"', $where);
    }

    public function testBuildWhere_Array()
    {
        $where = DBQuery_Splitter::buildWhere(array('foo' => 10, 'bar' => "blue"));
        $this->assertEquals('`foo` = 10 AND `bar` = "blue"', $where);
    }

    public function testBuildWhere_TwoParamsArray()
    {
        $where = DBQuery_Splitter::buildWhere('foo IN ? AND bar LIKE %?%', array(array(10, 20), "blue"));
        $this->assertEquals('`foo` IN (10, 20) AND `bar` LIKE "%blue%"', $where);
    }

    //--------


    public function testExtractSubsets_Select()
    {
        $set = DBQuery_Splitter::extractSubsets("SELECT * FROM relatie WHERE status = 1");
        $this->assertEquals(array("SELECT * FROM relatie WHERE status = 1"), array_map(array(__CLASS__, 'cleanQuery'), $set));
    }

    public function testExtractSubsets_SelectSubqueryInWhere()
    {
        $set = DBQuery_Splitter::extractSubsets("SELECT * FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
        $this->assertEquals(array("SELECT * FROM relatie WHERE id IN (#sub1) AND status = 1", "SELECT relatie_id FROM relatie_groep"), array_map(array(__CLASS__, 'cleanQuery'), $set));
    }

    public function testExtractSubsets_SelectSubqueryInJoin()
    {
        $set = DBQuery_Splitter::extractSubsets("SELECT * FROM relatie LEFT JOIN (SELECT relatie_id, COUNT(*) FROM contactpersoon) AS con_cnt ON relatie.id = con_cnt.relatie_id WHERE id IN (SELECT relatie_id FROM relatie_groep STRAIGHT JOIN (SELECT y, COUNT(x) FROM xy GROUP BY y) AS xy) AND status = 1");
        $this->assertEquals(array("SELECT * FROM relatie LEFT JOIN (#sub1) AS con_cnt ON relatie.id = con_cnt.relatie_id WHERE id IN (#sub2) AND status = 1", "SELECT relatie_id, COUNT(*) FROM contactpersoon", "SELECT relatie_id FROM relatie_groep STRAIGHT JOIN (#sub3) AS xy", "SELECT y, COUNT(x) FROM xy GROUP BY y"), array_map(array(__CLASS__, 'cleanQuery'), $set));
    }

    public function testExtractSubsets_Insert()
    {
        $set = DBQuery_Splitter::extractSubsets("INSERT INTO relatie_active SELECT * FROM relatie WHERE status = 1");
        $this->assertEquals(array("INSERT INTO relatie_active #sub1", "SELECT * FROM relatie WHERE status = 1"), array_map(array(__CLASS__, 'cleanQuery'), $set));
    }

    public function testExtractSubsets_InsertSubqueryInWhere()
    {
        $set = DBQuery_Splitter::extractSubsets("INSERT INTO relatie_active SELECT * FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
        $this->assertEquals(array("INSERT INTO relatie_active #sub1", "SELECT * FROM relatie WHERE id IN (#sub2) AND status = 1", "SELECT relatie_id FROM relatie_groep"), array_map(array(__CLASS__, 'cleanQuery'), $set));
    }

    // ---------


    public function testSplit_Select()
    {
        $parts = DBQuery_Splitter::split("SELECT");
        $this->assertEquals(array('select' => '', 'columns' => '', 'from' => '', 'where' => '', 'group by' => '', 'having' => '', 'order by' => '', 'limit' => '', 'options' => ''), array_map('trim', $parts));
    }

    public function testSplit_Select_Simple()
    {
        $parts = DBQuery_Splitter::split("SELECT id, description FROM `test`");
        $this->assertEquals(array('select' => '', 'columns' => 'id, description', 'from' => '`test`', 'where' => '', 'group by' => '', 'having' => '', 'order by' => '', 'limit' => '', 'options' => ''), array_map('trim', $parts));
    }

    public function testSplit_Select_Advanced()
    {
        $parts = DBQuery_Splitter::split("SELECT DISTINCTROW id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing FROM `test` INNER JOIN abc ON test.id = abc.id WHERE test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE' GROUP BY my_dd HAVING COUNT(1+3+xyz) < 100 LIMIT 15, 30 FOR UPDATE");
        $this->assertEquals(array('select' => 'DISTINCTROW', 'columns' => "id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing", 'from' => "`test` INNER JOIN abc ON test.id = abc.id", 'where' => "test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE'", 'group by' => "my_dd", 'having' => "COUNT(1+3+xyz) < 100", 'order by' => '', 'limit' => "15, 30", 'options' => "FOR UPDATE"), array_map('trim', $parts));
    }

    public function testSplit_Select_Subquery()
    {
        $parts = DBQuery_Splitter::split("SELECT id, description, VALUES(SELECT id, desc FROM subt WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs FROM `test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' ORDER BY abx.dd");
        $this->assertEquals(array('select' => '', 'columns' => "id, description, VALUES(SELECT id, desc FROM subt WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs", 'from' => "`test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc", 'where' => "abc.x IN (1,2,3,6,7) AND qq!='(SELECT)'", 'group by' => '', 'having' => '', 'order by' => 'abx.dd', 'limit' => '', 'options' => ''), array_map('trim', $parts));
    }

    public function testSplit_Select_SubqueryMadness()
    {
        $parts = DBQuery_Splitter::split("SELECT id, description, VALUES(SELECT id, desc FROM subt1 INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON subt1.id = subt2.p_id WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs FROM `test` INNER JOIN (SELECT * FROM abc INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON abc.id = subt2.p_id WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' AND x_id IN (SELECT id FROM x) ORDER BY abx.dd LIMIT 10");
        $this->assertEquals(array('select' => '', 'columns' => "id, description, VALUES(SELECT id, desc FROM subt1 INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON subt1.id = subt2.p_id WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs", 'from' => "`test` INNER JOIN (SELECT * FROM abc INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON abc.id = subt2.p_id WHERE i = 1 GROUP BY x) AS abc", 'where' => "abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' AND x_id IN (SELECT id FROM x)", 'group by' => '', 'having' => '', 'order by' => 'abx.dd', 'limit' => '10', 'options' => ''), array_map('trim', $parts));
    }

    public function testSplit_Select_Semicolon()
    {
        $parts = DBQuery_Splitter::split("SELECT id, description FROM `test`; Please ignore this");
        $this->assertEquals(array('select' => '', 'columns' => 'id, description', 'from' => '`test`', 'where' => '', 'group by' => '', 'having' => '', 'order by' => '', 'limit' => '', 'options' => ''), array_map('trim', $parts));
    }

    public function testJoinSelect_Simple()
    {
        $sql = DBQuery_Splitter::join(array('select' => '', 'columns' => 'id, description', 'from' => '`test`', 'where' => '', 'group by' => '', 'having' => '', 'order by' => '', 'limit' => '', 'options' => ''));
        $this->assertEquals("SELECT id, description FROM `test`", $sql);
    }

    public function testJoinSelect_Advanced()
    {
        $sql = DBQuery_Splitter::join(array('select' => 'DISTINCTROW', 'columns' => "id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing", 'from' => "`test` INNER JOIN abc ON test.id = abc.id", 'where' => "test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE'", 'group by' => "my_dd", 'having' => "COUNT(1+3+xyz) < 100", 'order by' => '', 'limit' => "15, 30", 'options' => "FOR UPDATE"));
        $this->assertEquals("SELECT DISTINCTROW id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing FROM `test` INNER JOIN abc ON test.id = abc.id WHERE test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE' GROUP BY my_dd HAVING COUNT(1+3+xyz) < 100 LIMIT 15, 30 FOR UPDATE", $sql);
    }

    public function testJoinSelect_Subquery()
    {
        $sql = DBQuery_Splitter::join(array('select' => '', 'columns' => "id, description", 'from' => "`test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc", 'where' => "abc.x IN (1,2,3,6,7) AND qq!='(SELECT)'", 'group by' => '', 'having' => '', 'order by' => 'abx.dd', 'limit' => '', 'options' => ''));
        $this->assertEquals("SELECT id, description FROM `test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' ORDER BY abx.dd", $sql);
    }

    public function testSplit_Insert()
    {
        $parts = DBQuery_Splitter::split("INSERT");
        $this->assertEquals(array('insert' => '', 'into' => '', 'columns' => '', 'set' => '', 'values' => '', 'query' => '', 'on duplicate key update' => ''), array_map('trim', $parts));
    }

    public function testSplit_InsertValuesSimple()
    {
        $parts = DBQuery_Splitter::split("INSERT INTO `test` VALUES (NULL, 'abc')");
        $this->assertEquals(array('insert' => '', 'into' => '`test`', 'columns' => '', 'set' => '', 'values' => "(NULL, 'abc')", 'query' => '', 'on duplicate key update' => ''), array_map('trim', $parts));
    }

    public function testSplit_ReplaceValuesSimple()
    {
        $parts = DBQuery_Splitter::split("REPLACE INTO `test` VALUES (NULL, 'abc')");
        $this->assertEquals(array('replace' => '', 'into' => '`test`', 'columns' => '', 'set' => '', 'values' => "(NULL, 'abc')", 'query' => '', 'on duplicate key update' => ''), array_map('trim', $parts));
    }

    public function testSplit_InsertValuesColumns()
    {
        $parts = DBQuery_Splitter::split("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10)");
        $this->assertEquals(array('insert' => '', 'into' => '`test`', 'columns' => "`id`, description, `values`", 'set' => '', 'values' => "(NULL, 'abc', 10)", 'query' => '', 'on duplicate key update' => ''), array_map('trim', $parts));
    }

    public function testSplit_InsertValuesMultiple()
    {
        $parts = DBQuery_Splitter::split("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10), (NULL, 'bb', 20), (NULL, 'cde', 30)");
        $this->assertEquals(array('insert' => '', 'into' => '`test`', 'columns' => "`id`, description, `values`", 'set' => '', 'values' => "(NULL, 'abc', 10), (NULL, 'bb', 20), (NULL, 'cde', 30)", 'query' => '', 'on duplicate key update' => ''), array_map('trim', $parts));
    }

    public function testSplit_InsertSetSimple()
    {
        $parts = DBQuery_Splitter::split("INSERT INTO `test` SET `id`=NULL, description = 'abc'");
        $this->assertEquals(array('insert' => '', 'into' => '`test`', 'columns' => '', 'set' => "`id`=NULL, description = 'abc'", 'values' => '', 'query' => '', 'on duplicate key update' => ''), array_map('trim', $parts));
    }

    public function testSplit_InsertSelectSimple()
    {
        $parts = DBQuery_Splitter::split("INSERT INTO `test` SELECT NULL, name FROM xyz");
        $this->assertEquals(array('insert' => '', 'into' => '`test`', 'columns' => '', 'set' => '', 'values' => '', 'query' => "SELECT NULL, name FROM xyz", 'on duplicate key update' => ''), array_map('trim', $parts));
    }

    public function testSplit_InsertSelectSubquery()
    {
        $parts = DBQuery_Splitter::split("INSERT INTO `test` SELECT NULL, name FROM xyz WHERE type IN (SELECT type FROM tt GROUP BY type HAVING SUM(qn) > 10)");
        $this->assertEquals(array('insert' => '', 'into' => '`test`', 'columns' => '', 'set' => '', 'values' => '', 'query' => "SELECT NULL, name FROM xyz WHERE type IN (SELECT type FROM tt GROUP BY type HAVING SUM(qn) > 10)", 'on duplicate key update' => ''), array_map('trim', $parts));
    }

    public function testJoinInsertValuesSimple()
    {
        $sql = DBQuery_Splitter::join(array('insert' => '', 'into' => '`test`', 'columns' => '', 'set' => '', 'values' => "(NULL, 'abc')", 'query' => '', 'on duplicate key update' => ''));
        $this->assertEquals("INSERT INTO `test` VALUES (NULL, 'abc')", $sql);
    }

    public function testJoinReplaceValuesSimple()
    {
        $sql = DBQuery_Splitter::join(array('replace' => '', 'into' => '`test`', 'columns' => '', 'set' => '', 'values' => "(NULL, 'abc')", 'query' => '', 'on duplicate key update' => ''));
        $this->assertEquals("REPLACE INTO `test` VALUES (NULL, 'abc')", $sql);
    }

    public function testJoinInsertValuesColumns()
    {
        $sql = DBQuery_Splitter::join(array('insert' => '', 'into' => '`test`', 'columns' => "`id`, description, `values`", 'set' => '', 'values' => "(NULL, 'abc', 10)", 'query' => '', 'on duplicate key update' => ''));
        $this->assertEquals("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10)", $sql);
    }

    public function testJoinInsertValuesMultiple()
    {
        $sql = DBQuery_Splitter::join(array('insert' => '', 'into' => '`test`', 'columns' => "`id`, description, `values`", 'set' => '', 'values' => "(NULL, 'abc', 10), (NULL, 'bb', 20), (NULL, 'cde', 30)", 'query' => '', 'on duplicate key update' => ''));
        $this->assertEquals("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10), (NULL, 'bb', 20), (NULL, 'cde', 30)", $sql);
    }

    public function testJoinInsertSelectSimple()
    {
        $sql = DBQuery_Splitter::join(array('insert' => '', 'into' => '`test`', 'columns' => '', 'set' => '', 'values' => '', 'query' => "SELECT NULL, name FROM xyz", 'on duplicate key update' => ''));
        $this->assertEquals("INSERT INTO `test` SELECT NULL, name FROM xyz", $sql);
    }

    public function testJoinInsertSelectSubquery()
    {
        $sql = DBQuery_Splitter::join(array('insert' => '', 'into' => '`test`', 'columns' => '', 'set' => '', 'values' => '', 'query' => "SELECT NULL, name FROM xyz WHERE type IN (SELECT type FROM tt GROUP BY type HAVING SUM(qn) > 10)", 'on duplicate key update' => ''));
        $this->assertEquals("INSERT INTO `test` SELECT NULL, name FROM xyz WHERE type IN (SELECT type FROM tt GROUP BY type HAVING SUM(qn) > 10)", $sql);
    }

    public function testSplit_UpdateSimple()
    {
        $parts = DBQuery_Splitter::split("UPDATE `test` SET status='ACTIVE' WHERE id=10");
        $this->assertEquals(array('update' => '', 'table' => '`test`', 'set' => "status='ACTIVE'", 'where' => 'id=10', 'limit' => ''), array_map('trim', $parts));
    }

    public function testSplit_UpdateAdvanced()
    {
        $parts = DBQuery_Splitter::split("UPDATE `test` LEFT JOIN atst ON `test`.id = atst.idTest SET fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE' WHERE id = 10 LIMIT 20 OFFSET 10");
        $this->assertEquals(array('update' => '', 'table' => '`test` LEFT JOIN atst ON `test`.id = atst.idTest', 'set' => "fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE'", 'where' => 'id = 10', 'limit' => '20 OFFSET 10'), array_map('trim', $parts));
    }

    public function testSplit_UpdateSubquery()
    {
        $parts = DBQuery_Splitter::split("UPDATE `test` LEFT JOIN (SELECT idTest, a, f, count(*) AS cnt FROM atst) AS atst ON `test`.id = atst.idTest SET fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE' WHERE id IN (SELECT id FROM whatever LIMIT 100)");
        $this->assertEquals(array('update' => '', 'table' => '`test` LEFT JOIN (SELECT idTest, a, f, count(*) AS cnt FROM atst) AS atst ON `test`.id = atst.idTest', 'set' => "fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE'", 'where' => 'id IN (SELECT id FROM whatever LIMIT 100)', 'limit' => ''), array_map('trim', $parts));
    }

    public function testJoin_UpdateSimple()
    {
        $sql = DBQuery_Splitter::join(array('update' => '', 'table' => '`test`', 'set' => "status='ACTIVE'", 'where' => 'id=10', 'limit' => ''));
        $this->assertEquals("UPDATE `test` SET status='ACTIVE' WHERE id=10", $sql);
    }

    public function testJoin_UpdateAdvanced()
    {
        $sql = DBQuery_Splitter::join(array('update' => '', 'table' => '`test` LEFT JOIN atst ON `test`.id = atst.idTest', 'set' => "fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE'", 'where' => 'id = 10', 'limit' => '20 OFFSET 10'));
        $this->assertEquals("UPDATE `test` LEFT JOIN atst ON `test`.id = atst.idTest SET fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE' WHERE id = 10 LIMIT 20 OFFSET 10", $sql);
    }

    public function testSplit_DeleteSimple()
    {
        $parts = DBQuery_Splitter::split("DELETE FROM `test` WHERE id=10");
        $this->assertEquals(array('delete' => '', 'columns' => '', 'from' => '`test`', 'where' => 'id=10', 'order by' => '', 'limit' => ''), array_map('trim', $parts));
    }

    public function testSplit_DeleteAdvanced()
    {
        $parts = DBQuery_Splitter::split("DELETE `test`.* FROM `test` INNER JOIN `dude where is my car`.`import` AS dude_import ON `test`.ref = dude_import.ref WHERE dude_import.sql NOT LIKE '% on duplicate key update' AND status = 10 ORDER BY xyz LIMIT 1");
        $this->assertEquals(array('delete' => '', 'columns' => '`test`.*', 'from' => '`test` INNER JOIN `dude where is my car`.`import` AS dude_import ON `test`.ref = dude_import.ref', 'where' => "dude_import.sql NOT LIKE '% on duplicate key update' AND status = 10", 'order by' => 'xyz', 'limit' => '1'), array_map('trim', $parts));
    }

    public function testSplit_DeleteSubquery()
    {
        $parts = DBQuery_Splitter::split("DELETE `test`.* FROM `test` INNER JOIN (SELECT * FROM dude_import GROUP BY x_id WHERE status = 'OK' HAVING COUNT(*) > 1) AS dude_import ON `test`.ref = dude_import.ref WHERE status = 10");
        $this->assertEquals(array('delete' => '', 'columns' => '`test`.*', 'from' => "`test` INNER JOIN (SELECT * FROM dude_import GROUP BY x_id WHERE status = 'OK' HAVING COUNT(*) > 1) AS dude_import ON `test`.ref = dude_import.ref", 'where' => "status = 10", 'order by' => '', 'limit' => ''), array_map('trim', $parts));
    }

    public function testJoin_DeleteSimple()
    {
        $sql = DBQuery_Splitter::join(array('delete' => '', 'columns' => '', 'from' => '`test`', 'where' => 'id=10', 'order by' => '', 'limit' => ''));
        $this->assertEquals("DELETE FROM `test` WHERE id=10", $sql);
    }

    public function testJoin_DeleteAdvanced()
    {
        $sql = DBQuery_Splitter::join(array('delete' => '', 'columns' => '`test`.*', 'from' => '`test` INNER JOIN `dude where is my car`.`import` AS dude_import ON `test`.ref = dude_import.ref', 'where' => "dude_import.sql NOT LIKE '% on duplicate key update' AND status = 10", 'order by' => 'xyz', 'limit' => '1'));
        $this->assertEquals("DELETE `test`.* FROM `test` INNER JOIN `dude where is my car`.`import` AS dude_import ON `test`.ref = dude_import.ref WHERE dude_import.sql NOT LIKE '% on duplicate key update' AND status = 10 ORDER BY xyz LIMIT 1", $sql);
    }

    public function testSplit_Truncate()
    {
        $parts = DBQuery_Splitter::split("TRUNCATE `test`");
        $this->assertEquals(array('truncate' => '', 'table' => '`test`'), array_map('trim', $parts));
    }

    public function testJoin_Truncate()
    {
        $sql = DBQuery_Splitter::join(array('truncate' => '', 'table' => '`test`'));
        $this->assertEquals("TRUNCATE `test`", $sql);
    }

    public function testSplit_Set()
    {
        $parts = DBQuery_Splitter::split("SET abc=10, @def='test'");
        $this->assertEquals(array('set' => "abc=10, @def='test'"), array_map('trim', $parts));
    }

    public function testJoin_Set()
    {
        $sql = DBQuery_Splitter::join(array('set' => "abc=10, @def='test'"));
        $this->assertEquals("SET abc=10, @def='test'", $sql);
    }

    public function testSplit_Fail()
    {
        $this->setExpectedException('Exception', "Unable to split ALTER TABLE query");
        DBQuery_Splitter::split("ALTER TABLE ADD column `foo` varchar(255) NULL");
    }

    //--------


    public function testSplitColumns_Simple()
    {
        $columns = DBQuery_Splitter::splitColumns("abc, xyz, test");
        $this->assertEquals(array("abc", "xyz", "test"), $columns);
    }

    public function testSplitColumns_Advanced()
    {
        $columns = DBQuery_Splitter::splitColumns("abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')), test, 10+3 AS `bb`, 'Ho, Hi' AS HoHi, 22");
        $this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))", "test", "10+3 AS `bb`", "'Ho, Hi' AS HoHi", "22"), $columns);
    }

    public function testSplitColumns_Select()
    {
        $columns = DBQuery_Splitter::splitColumns("SELECT abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')), test, 10+3 AS `bb`, 'Ho, Hi' AS HoHi, 22 FROM test INNER JOIN contact WHERE a='X FROM Y'");
        $this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))", "test", "10+3 AS `bb`", "'Ho, Hi' AS HoHi", "22"), $columns);
    }

    public function testSplitColumns_SelectSubquery()
    {
        $columns = DBQuery_Splitter::splitColumns("SELECT abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')), x IN (SELECT id FROM xy) AS subq FROM test");
        $this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))", "x IN (SELECT id FROM xy) AS subq"), $columns);
    }

    public function testSplitColumns_SelectSubFrom()
    {
        $columns = DBQuery_Splitter::splitColumns("SELECT abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')) FROM test INNER JOIN (SELECT id, desc FROM xy) AS subq ON test.id = subq.id");
        $this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))"), $columns);
    }

    public function testSplitColumns_SelectRealLifeExample()
    {
        $columns = DBQuery_Splitter::splitColumns("SELECT relation.id, IF( name = '', CONVERT( concat_name(last_name, suffix, first_name, '')USING latin1 ) , name ) AS fullname FROM relation LEFT JOIN relation_person_type ON relation.id = relation_person_type.relation_id LEFT JOIN person_type ON person_type.id = relation_person_type.person_type_id WHERE person_type_id =5 ORDER BY fullname");
        $this->assertEquals(array("relation.id", "IF( name = '', CONVERT( concat_name(last_name, suffix, first_name, '')USING latin1 ) , name ) AS fullname"), $columns);
    }

    public function testSplitColumns_InsertValues()
    {
        $columns = DBQuery_Splitter::splitColumns("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10)");
        $this->assertEquals(array('`id`', 'description', '`values`'), $columns);
    }

    public function testSplitColumns_InsertSelect()
    {
        $columns = DBQuery_Splitter::splitColumns("INSERT INTO `test` (`id`, description, `values`) SELECT product_id, title, 22 AS values FROM `abc`");
        $this->assertEquals(array('`id`', 'description', '`values`'), $columns);
    }

    public function testSplitColumns_Delete()
    {
        $columns = DBQuery_Splitter::splitColumns("DELETE test.* FROM `test` INNER JOIN `xyz` ON test.id=xyz.test_id");
        $this->assertEquals(array("test.*"), $columns);
    }

    public function testSplitSet()
    {
        $set = DBQuery_Splitter::splitSet("SET @abc=18, def=CONCAT('test', '123', DATE_FORMAT(NOW(), '%d-%m-%Y %H:%M')), @uid=NULL");
        $this->assertEquals(array("@abc" => "18", "def" => "CONCAT('test', '123', DATE_FORMAT(NOW(), '%d-%m-%Y %H:%M'))", "@uid" => "NULL"), $set);
    }

    public function testSplitSet_Insert()
    {
        $set = DBQuery_Splitter::splitSet("INSERT INTO `test` SET id=1, description='test', `values`=22");
        $this->assertEquals(array('id' => '1', "description" => "'test'", '`values`' => '22'), $set);
    }

    public function testSplitSet_Update()
    {
        $set = DBQuery_Splitter::splitSet("UPDATE `test` INNER JOIN `xyz` ON test.id=xyz.test_id SET description='test', `values`=22 WHERE test.id=1");
        $this->assertEquals(array("description" => "'test'", '`values`' => '22'), $set);
    }

    // -------


    public function testSplitTables_Simple()
    {
        $tables = DBQuery_Splitter::splitTables("abc, xyz, mysql.test");
        $this->assertEquals(array("abc" => "abc", "xyz" => "xyz", "test" => "mysql.test"), $tables);
    }

    public function testSplitTables_Alias()
    {
        $tables = DBQuery_Splitter::splitTables("abc `a`, `xyz`, mysql.test AS tt");
        $this->assertEquals(array("a" => "abc", "xyz" => "`xyz`", "tt" => "mysql.test"), $tables);
    }

    public function testSplitTables_Join()
    {
        $tables = DBQuery_Splitter::splitTables("abc `a` INNER JOIN ufd.zzz AS `xyz` ON abc.id = xyz.abc_id LEFT JOIN def ON abc.x IN (SELECT abc FROM `xyz_link`) AND abc.y = MYFUNCT(10, 12, xyz.abc_id) STRAIGHT_JOIN tuf, qwerty");
        $this->assertEquals(array("a" => "abc", "xyz" => "ufd.zzz", "def" => "def", "tuf" => "tuf", "qwerty" => "qwerty"), $tables);
    }

    public function testSplitTables_Subjoin()
    {
        $tables = DBQuery_Splitter::splitTables("abc `a` INNER JOIN (ufd.zzz AS `xyz` LEFT JOIN def ON abc.x IN (SELECT abc FROM `xyz_link`) AND abc.y = def.id, qwerty) ON abc.id = MYFUNCT(10, 12, xyz.abc_id) STRAIGHT_JOIN tuf");
        $this->assertEquals(array("a" => "abc", "xyz" => "ufd.zzz", "def" => "def", "qwerty" => "qwerty", "tuf" => "tuf"), $tables);
    }

    public function testSplitTables_Subquery()
    {
        $tables = DBQuery_Splitter::splitTables("abc `a` INNER JOIN (SELECT * FROM ufd.zzz AS `xyz` LEFT JOIN def ON abc.y = def.id, qwerty) AS xyz ON abc.id = MYFUNCT(10, 12, xyz.abc_id) STRAIGHT_JOIN tuf");
        $this->assertEquals(array("a" => "abc", "xyz" => "(SELECT * FROM ufd.zzz AS `xyz` LEFT JOIN def ON abc.y = def.id, qwerty)", "tuf" => "tuf"), $tables);
    }

    public function testSplitTables_Select()
    {
        $tables = DBQuery_Splitter::splitTables("SELECT aaa, zzz FROM abc `a` INNER JOIN ufd.zzz AS `xyz` ON abc.id = xyz.abc_id LEFT JOIN def ON abc.x IN (SELECT abc FROM `xyz_link`) AND abc.y = MYFUNCT(10, 12, xyz.abc_id) STRAIGHT_JOIN tuf, qwerty WHERE a='X FROM Y'");
        $this->assertEquals(array("a" => "abc", "xyz" => "ufd.zzz", "def" => "def", "tuf" => "tuf", "qwerty" => "qwerty"), $tables);
    }

    public function testSplitTables_InsertValues()
    {
        $tables = DBQuery_Splitter::splitTables("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10)");
        $this->assertEquals(array("test" => "`test`"), $tables);
    }

    public function testSplitTables_InsertSelect()
    {
        $tables = DBQuery_Splitter::splitTables("INSERT INTO `test` (`id`, description, `values`) SELECT product_id, title, 22 AS values FROM `abc`");
        $this->assertEquals(array("test" => "`test`"), $tables);
    }

    public function testSplitTables_InsertSet()
    {
        $tables = DBQuery_Splitter::splitTables("INSERT INTO `test` SET id=1, description='test', `values`=22");
        $this->assertEquals(array("test" => "`test`"), $tables);
    }

    public function testSplitTables_Update()
    {
        $tables = DBQuery_Splitter::splitTables("UPDATE `test` INNER JOIN `xyz` ON test.id=xyz.test_id SET description='test', `values`=22 WHERE test.id=1");
        $this->assertEquals(array("test" => "`test`", "xyz" => "`xyz`"), $tables);
    }

    public function testSplitTables_Delete()
    {
        $tables = DBQuery_Splitter::splitTables("DELETE test.* FROM `test` INNER JOIN `xyz` ON test.id=xyz.test_id");
        $this->assertEquals(array("test" => "`test`", "xyz" => "`xyz`"), $tables);
    }

    //--------


    public function testSplitLimit()
    {
        $limit = DBQuery_Splitter::splitLimit("10");
        $this->assertEquals(array(10, null), $limit);
    }

    public function testSplitLimit_Comma()
    {
        $limit = DBQuery_Splitter::splitLimit("50, 10");
        $this->assertEquals(array(10, 50), $limit);
    }

    public function testSplitLimit_Offset()
    {
        $limit = DBQuery_Splitter::splitLimit("10 OFFSET 50");
        $this->assertEquals(array(10, 50), $limit);
    }

    public function testSplitLimit_Fail()
    {
        $this->setExpectedException('Exception', "Invalid limit statement 'foo, bar'");
        DBQuery_Splitter::splitLimit("foo, bar");
    }

    public function testSplitLimit_Select()
    {
        $limit = DBQuery_Splitter::splitLimit("SELECT * FROM foo LIMIT 10");
        $this->assertEquals(array(10, null), $limit);
    }

    public function testSplitLimit_SelectNoLimit()
    {
        $limit = DBQuery_Splitter::splitLimit("SELECT * FROM foo");
        $this->assertEquals(array(null, null), $limit);
    }

    public function testSplitLimit_Truncate()
    {
        $this->setExpectedException('Exception', "A TRUNCATE query doesn't have a LIMIT part.");
        DBQuery_Splitter::splitLimit("TRUNCATE foo");
    }

    //--------


    public function testBuildCountQuery_Simple()
    {
        $sql = DBQuery_Splitter::buildCountQuery("SELECT * FROM foo");
        $this->assertEquals("SELECT COUNT(*) FROM foo", $sql);
    }

    public function testBuildCountQuery_Select()
    {
        $sql = DBQuery_Splitter::buildCountQuery("SELECT * FROM foo INNER JOIN bar ON foo.id = bar.foo_id WHERE abc = 10 LIMIT 50");
        $this->assertEquals("SELECT LEAST(COUNT(*), 50) FROM foo INNER JOIN bar ON foo.id = bar.foo_id WHERE abc = 10", $sql);
    }

    public function testBuildCountQuery_Select_Offset()
    {
        $sql = DBQuery_Splitter::buildCountQuery("SELECT * FROM foo INNER JOIN bar ON foo.id = bar.foo_id WHERE abc = 10 LIMIT 50 OFFSET 200");
        $this->assertEquals("SELECT LEAST(COUNT(*), 50, COUNT(*) - 200) FROM foo INNER JOIN bar ON foo.id = bar.foo_id WHERE abc = 10", $sql);
    }

    public function testBuildCountQuery_Select_AllRows()
    {
        $sql = DBQuery_Splitter::buildCountQuery("SELECT * FROM foo INNER JOIN bar ON foo.id = bar.foo_id WHERE abc = 10 LIMIT 50", DBQuery::ALL_ROWS);
        $this->assertEquals("SELECT COUNT(*) FROM foo INNER JOIN bar ON foo.id = bar.foo_id WHERE abc = 10", $sql);
    }

    public function testBuildCountQuery_Distinct()
    {
        $sql = DBQuery_Splitter::buildCountQuery("SELECT DISTINCT id FROM foo");
        $this->assertEquals("SELECT COUNT(DISTINCT id) FROM foo", $sql);
    }

    public function testBuildCountQuery_GroupBy()
    {
        $sql = DBQuery_Splitter::buildCountQuery("SELECT * FROM foo GROUP BY abc, xyz");
        $this->assertEquals("SELECT COUNT(DISTINCT abc, xyz) FROM foo", $sql);
    }

    public function testBuildCountQuery_Having()
    {
        $sql = DBQuery_Splitter::buildCountQuery("SELECT * FROM foo GROUP BY abc, xyz HAVING COUNT(*) > 10");
        $this->assertEquals("SELECT COUNT(*) FROM (SELECT * FROM foo GROUP BY abc, xyz HAVING COUNT(*) > 10) AS q", $sql);
    }

    public function testBuildCountQuery_Update()
    {
        $sql = DBQuery_Splitter::buildCountQuery("UPDATE foo INNER JOIN bar ON foo.id = bar.foo_id SET xyz = 20 WHERE abc = 10 LIMIT 50");
        $this->assertEquals("SELECT LEAST(COUNT(*), 50) FROM foo INNER JOIN bar ON foo.id = bar.foo_id WHERE abc = 10", $sql);
    }

    public function testBuildCountQuery_Delete()
    {
        $sql = DBQuery_Splitter::buildCountQuery("DELETE FROM foo WHERE abc = 10 LIMIT 50");
        $this->assertEquals("SELECT LEAST(COUNT(*), 50) FROM foo WHERE abc = 10", $sql);
    }

    public function testBuildCountQuery_DeleteJoin()
    {
        $sql = DBQuery_Splitter::buildCountQuery("DELETE foo.* FROM foo INNER JOIN bar ON foo.id = bar.foo_id WHERE abc = 10");
        $this->assertEquals("SELECT COUNT(*) FROM foo INNER JOIN bar ON foo.id = bar.foo_id WHERE abc = 10", $sql);
    }

}