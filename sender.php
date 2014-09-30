<?php


class Sender
{
    private static $instance;
    private $file;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if( empty( self::$instance ) )
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function HandShake( $data )
    {

        $url =  appRegistry::getInstance()->getUrl() ;

       return  $this->SendQuery( $data, $url );

    }

    public function SendData( $data )
    {
        $url =  appRegistry::getInstance()->getUrl2() ;
        
        return  $this->SendQuery( $data, $url, true );
    }

    private function SendQuery( $code, $url, $put = false )
    {

        $data = json_encode( $code );


        $curl = curl_init( $url );
        curl_setopt($curl, CURLOPT_URL, $url );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);


        if($put)
        {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");

        }
        else
        {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POST, 1);

        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $content = curl_exec($curl);


        $data = json_decode( $content, true );
        if($data['status'] !== 'ok')
            throw new Exception('Запрос не удачен: '.$data['message']);
        return $data;
    }


}

/*
 *
 */
abstract class Registry
{

    abstract protected function get( $key );
    abstract protected function set( $key, $value );
}

/*
 *  Настройки для доступа к API
 */
class appRegistry extends Registry
{
    private static $instance;
    private $data;

    private function __construct()
    {
        $this->data = array();
        $this->setData( 'params.txt' );
    }

    public static function getInstance()
    {
        if( empty( self::$instance ) )
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function get( $key )
    {
       return  $this->data[$key];
    }

    protected function set( $key, $value )
    {
        $this->data[ trim( $key) ] = trim( $value );
    }

    protected function setData( $file )
    {
        if( !is_file( $file ) )
            throw new Exception('Файл конфига - не найден');
        if( !is_readable( $file) )
            throw new Exception('Файл конфига - нет прав на чтение');

        $params = file($file);

        foreach( $params as $v)
        {
            list( $key, $value ) = explode( '-', $v );
            $this->set( trim( $key ), trim( $value) );
        }
    }

    public function __call( $method, $argv )
    {
         if(substr_count($method, 'get') > 0 )
         {
             $name = substr($method, 3);
             return $this->get($name);
         }

    }

    public function setToken( $token )
    {
        $this->set('Token',  $token);
        $this->data['Url2'] .= trim( $token );

    }

}

 try{

        $data['name'] = appRegistry::getInstance()->getName();
        $data['secret'] = appRegistry::getInstance()->getSecret() ;

        $hand = Sender::getInstance()->HandShake( $data );

        echo 'соединение установлено. <br>';
        echo "токен получен - {$hand['token']}<br>";
        echo 'отсылаем листинг.... <br>';

        appRegistry::getInstance()->setToken($hand['token']) ;

        $data['text'] = file_get_contents( __FILE__ );
        Sender::getInstance()->SendData( $data );

        echo 'Работа завершена успешно';
 }
 catch(Exception $e)
 {
     echo 'Вылетело исключение: <br>';
     echo $e->getMessage();
 }


 ?>
