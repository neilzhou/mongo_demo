<?php
/**
 * undocumented class
 *
 * @package default
 * @subpackage default
 * @author Neil.zhou
 */
class TimingMongoInstance extends AppModel
{
    public $primaryKey = '_id';
    public $mongoSchema = array(
        'host' => array('type' => 'string'),
        'ip' => array('type' => 'string'),
        'port' => array('type' => 'string'),
        'created' => array('type' => 'datetime'),
        'modified' => array('type' => 'datetime'),
    );

    /**
     * save or update Timing host and port
     *
     * @return int | false
     */
    public function saveOrUpdate($host, $ip, $port)
    {
        $existed = $this->find('first', array(
            'conditions' => array(
                'host' => $host,
            )
        ));
        if (!empty($existed)) {
            return $existed[$this->alias]['_id'];
        }
        $this->create();
        $this->save(array('host'=>$host, 'ip'=> $ip, 'port'=>$port . ''));
        return $this->id;
    }
    
    
} // END class 
