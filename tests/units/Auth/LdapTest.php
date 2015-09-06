<?php

namespace Auth;

require_once __DIR__.'/../Base.php';

function ldap_connect($hostname, $port)
{
    return LdapTest::$functions->ldap_connect($hostname, $port);
}

function ldap_set_option()
{
}

function ldap_bind($link_identifier, $bind_rdn, $bind_password)
{
    return LdapTest::$functions->ldap_bind($link_identifier, $bind_rdn, $bind_password);
}

function ldap_search($link_identifier, $base_dn, $filter, array $attributes)
{
    return LdapTest::$functions->ldap_search($link_identifier, $base_dn, $filter, $attributes);
}

function ldap_get_entries($link_identifier, $result_identifier)
{
    return LdapTest::$functions->ldap_get_entries($link_identifier, $result_identifier);
}

class LdapTest extends \Base
{
    public static $functions;
    private $ldap;

    public function setUp()
    {
        parent::setup();

        $this->ldap = $ldap = new Ldap($this->container);

        self::$functions = $this
            ->getMockBuilder('stdClass')
            ->setMethods(array(
                'ldap_connect',
                'ldap_set_option',
                'ldap_bind',
                'ldap_search',
                'ldap_get_entries',
            ))
            ->getMock();
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$functions = null;
    }

    public function testConnectSuccess()
    {
        self::$functions
            ->expects($this->once())
            ->method('ldap_connect')
            ->with(
                $this->equalTo('my_ldap_server'),
                $this->equalTo(389)
            )
            ->will($this->returnValue('my_ldap_resource'));

        $this->assertNotFalse($this->ldap->connect('my_ldap_server'));
    }

    public function testConnectFailure()
    {
        self::$functions
            ->expects($this->once())
            ->method('ldap_connect')
            ->with(
                $this->equalTo('my_ldap_server'),
                $this->equalTo(389)
            )
            ->will($this->returnValue(false));

        $this->assertFalse($this->ldap->connect('my_ldap_server'));
    }

    public function testBindAnonymous()
    {
        self::$functions
            ->expects($this->once())
            ->method('ldap_bind')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo(null),
                $this->equalTo(null)
            )
            ->will($this->returnValue(true));

        $this->assertTrue($this->ldap->bind('my_ldap_connection', 'my_user', 'my_password', 'anonymous'));
    }

    public function testBindUser()
    {
        self::$functions
            ->expects($this->once())
            ->method('ldap_bind')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo('uid=my_user'),
                $this->equalTo('my_password')
            )
            ->will($this->returnValue(true));

        $this->assertTrue($this->ldap->bind('my_ldap_connection', 'my_user', 'my_password', 'user', 'uid=%s', 'something'));
    }

    public function testBindProxy()
    {
        self::$functions
            ->expects($this->once())
            ->method('ldap_bind')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo('someone'),
                $this->equalTo('something')
            )
            ->will($this->returnValue(true));

        $this->assertTrue($this->ldap->bind('my_ldap_connection', 'my_user', 'my_password', 'proxy', 'someone', 'something'));
    }

    public function testSearchSuccess()
    {
        $entries = array(
            'count' => 1,
            0 => array(
                'count' => 2,
                'dn' => 'uid=my_user,ou=People,dc=kanboard,dc=local',
                'displayname' => array(
                    'count' => 1,
                    0 => 'My user',
                ),
                'mail' => array(
                    'count' => 2,
                    0 => 'user1@localhost',
                    1 => 'user2@localhost',
                ),
                0 => 'displayname',
                1 => 'mail',
            )
        );

        $expected = array(
            'username' => 'my_user',
            'name' => 'My user',
            'email' => 'user1@localhost',
            'is_admin' => 0,
            'is_project_admin' => 0,
            'is_ldap_user' => 1,
        );

        self::$functions
            ->expects($this->at(0))
            ->method('ldap_search')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo('ou=People,dc=kanboard,dc=local'),
                $this->equalTo('uid=my_user'),
                $this->equalTo($this->ldap->getProfileAttributes())
            )
            ->will($this->returnValue('my_result_identifier'));

        self::$functions
            ->expects($this->at(1))
            ->method('ldap_get_entries')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo('my_result_identifier')
            )
            ->will($this->returnValue($entries));

        self::$functions
            ->expects($this->at(2))
            ->method('ldap_bind')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo('uid=my_user,ou=People,dc=kanboard,dc=local'),
                $this->equalTo('my_password')
            )
            ->will($this->returnValue(true));

        $this->assertEquals($expected, $this->ldap->getProfile('my_ldap_connection', 'my_user', 'my_password', 'ou=People,dc=kanboard,dc=local', 'uid=%s'));
    }

    public function testSearchWithBadPassword()
    {
        $entries = array(
            'count' => 1,
            0 => array(
                'count' => 2,
                'dn' => 'uid=my_user,ou=People,dc=kanboard,dc=local',
                'displayname' => array(
                    'count' => 1,
                    0 => 'My user',
                ),
                'mail' => array(
                    'count' => 2,
                    0 => 'user1@localhost',
                    1 => 'user2@localhost',
                ),
                0 => 'displayname',
                1 => 'mail',
            )
        );

        self::$functions
            ->expects($this->at(0))
            ->method('ldap_search')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo('ou=People,dc=kanboard,dc=local'),
                $this->equalTo('uid=my_user'),
                $this->equalTo($this->ldap->getProfileAttributes())
            )
            ->will($this->returnValue('my_result_identifier'));

        self::$functions
            ->expects($this->at(1))
            ->method('ldap_get_entries')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo('my_result_identifier')
            )
            ->will($this->returnValue($entries));

        self::$functions
            ->expects($this->at(2))
            ->method('ldap_bind')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo('uid=my_user,ou=People,dc=kanboard,dc=local'),
                $this->equalTo('my_password')
            )
            ->will($this->returnValue(false));

        $this->assertFalse($this->ldap->getProfile('my_ldap_connection', 'my_user', 'my_password', 'ou=People,dc=kanboard,dc=local', 'uid=%s'));
    }

    public function testSearchWithUserNotFound()
    {
        self::$functions
            ->expects($this->at(0))
            ->method('ldap_search')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo('ou=People,dc=kanboard,dc=local'),
                $this->equalTo('uid=my_user'),
                $this->equalTo($this->ldap->getProfileAttributes())
            )
            ->will($this->returnValue('my_result_identifier'));

        self::$functions
            ->expects($this->at(1))
            ->method('ldap_get_entries')
            ->with(
                $this->equalTo('my_ldap_connection'),
                $this->equalTo('my_result_identifier')
            )
            ->will($this->returnValue(array()));

        $this->assertFalse($this->ldap->getProfile('my_ldap_connection', 'my_user', 'my_password', 'ou=People,dc=kanboard,dc=local', 'uid=%s'));
    }

    public function testSuccessfulAuthentication()
    {
        $this->container['userSession'] = $this
            ->getMockBuilder('\Model\UserSession')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('refresh'))
            ->getMock();

        $this->container['user'] = $this
            ->getMockBuilder('\Model\User')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('getByUsername'))
            ->getMock();

        $ldap = $this
            ->getMockBuilder('\Auth\Ldap')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('findUser'))
            ->getMock();

        $ldap
            ->expects($this->once())
            ->method('findUser')
            ->with(
                $this->equalTo('user'),
                $this->equalTo('password')
            )
            ->will($this->returnValue(array('username' => 'user', 'name' => 'My user', 'email' => 'user@here')));

        $this->container['user']
            ->expects($this->once())
            ->method('getByUsername')
            ->with(
                $this->equalTo('user')
            )
            ->will($this->returnValue(array('id' => 2, 'username' => 'user', 'is_ldap_user' => 1)));

        $this->container['userSession']
            ->expects($this->once())
            ->method('refresh');

        $this->assertTrue($ldap->authenticate('user', 'password'));
    }

    public function testAuthenticationWithExistingLocalUser()
    {
        $this->container['userSession'] = $this
            ->getMockBuilder('\Model\UserSession')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('refresh'))
            ->getMock();

        $this->container['user'] = $this
            ->getMockBuilder('\Model\User')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('getByUsername'))
            ->getMock();

        $ldap = $this
            ->getMockBuilder('\Auth\Ldap')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('findUser'))
            ->getMock();

        $ldap
            ->expects($this->once())
            ->method('findUser')
            ->with(
                $this->equalTo('user'),
                $this->equalTo('password')
            )
            ->will($this->returnValue(array('username' => 'user', 'name' => 'My user', 'email' => 'user@here')));

        $this->container['user']
            ->expects($this->once())
            ->method('getByUsername')
            ->with(
                $this->equalTo('user')
            )
            ->will($this->returnValue(array('id' => 2, 'username' => 'user', 'is_ldap_user' => 0)));

        $this->container['userSession']
            ->expects($this->never())
            ->method('refresh');

        $this->assertFalse($ldap->authenticate('user', 'password'));
    }

    public function testAuthenticationWithAutomaticAccountCreation()
    {
        $ldap_profile = array('username' => 'user', 'name' => 'My user', 'email' => 'user@here');

        $this->container['userSession'] = $this
            ->getMockBuilder('\Model\UserSession')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('refresh'))
            ->getMock();

        $this->container['user'] = $this
            ->getMockBuilder('\Model\User')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('getByUsername', 'create'))
            ->getMock();

        $ldap = $this
            ->getMockBuilder('\Auth\Ldap')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('findUser'))
            ->getMock();

        $ldap
            ->expects($this->at(0))
            ->method('findUser')
            ->with(
                $this->equalTo('user'),
                $this->equalTo('password')
            )
            ->will($this->returnValue($ldap_profile));

        $this->container['user']
            ->expects($this->at(0))
            ->method('getByUsername')
            ->with(
                $this->equalTo('user')
            )
            ->will($this->returnValue(null));

        $this->container['user']
            ->expects($this->at(1))
            ->method('create')
            ->with(
                $this->equalTo($ldap_profile)
            )
            ->will($this->returnValue(true));

        $this->container['user']
            ->expects($this->at(2))
            ->method('getByUsername')
            ->with(
                $this->equalTo('user')
            )
            ->will($this->returnValue(array('id' => 2, 'username' => 'user', 'is_ldap_user' => 1)));

        $this->container['userSession']
            ->expects($this->once())
            ->method('refresh');

        $this->assertTrue($ldap->authenticate('user', 'password'));
    }

    public function testAuthenticationWithAutomaticAccountCreationFailed()
    {
        $ldap_profile = array('username' => 'user', 'name' => 'My user', 'email' => 'user@here');

        $this->container['userSession'] = $this
            ->getMockBuilder('\Model\UserSession')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('refresh'))
            ->getMock();

        $this->container['user'] = $this
            ->getMockBuilder('\Model\User')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('getByUsername', 'create'))
            ->getMock();

        $ldap = $this
            ->getMockBuilder('\Auth\Ldap')
            ->setConstructorArgs(array($this->container))
            ->setMethods(array('findUser'))
            ->getMock();

        $ldap
            ->expects($this->at(0))
            ->method('findUser')
            ->with(
                $this->equalTo('user'),
                $this->equalTo('password')
            )
            ->will($this->returnValue($ldap_profile));

        $this->container['user']
            ->expects($this->at(0))
            ->method('getByUsername')
            ->with(
                $this->equalTo('user')
            )
            ->will($this->returnValue(null));

        $this->container['user']
            ->expects($this->at(1))
            ->method('create')
            ->with(
                $this->equalTo($ldap_profile)
            )
            ->will($this->returnValue(false));

        $this->container['userSession']
            ->expects($this->never())
            ->method('refresh');

        $this->assertFalse($ldap->authenticate('user', 'password'));
    }
}
