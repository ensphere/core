<?php

namespace EnsphereCore\Libs\Config;

use App;

class Database
{

    /**
     * If on local environment, DB_SOCKET is an available .env option, if we can find a MAMP socket, that's the default
     *
     * @param $array
     * @return mixed
     */
    public static function mySQLconnection( $array )
    {
        if ( env( 'APP_ENV' ) === 'local' && in_array( env( 'DB_HOST' ), [ 'localhost', '127.0.0.1' ] ) ) {
            $path = '/Applications/MAMP/tmp/mysql/mysql.sock';
            $mampSocket = ( file_exists( $path ) ) ? $path : '';
            $array['unix_socket'] = env( 'DB_SOCKET', $mampSocket );
        }
        return $array;
    }

}
