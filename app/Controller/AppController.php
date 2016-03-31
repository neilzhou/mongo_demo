<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {
    public $layout = 'demo';
    public $helpers = array(
        'Session',
        'Html' => array(
            'className' => 'BoostCake.BoostCakeHtml',
        ),
        'Form' => array(
            'className' => 'BoostCake.BoostCakeForm',
        ),
        'Paginator' => array(
            'className' => 'BoostCake.BoostCakePaginator',
        )
    );
    //public $components = array(
		//'Auth' => array(
			//'flash' => array(
				//'element' => 'alert',
				//'key' => 'auth',
				//'params' => array(
					//'plugin' => 'BoostCake',
					//'class' => 'alert-error'
				//)
			//)
		//)
	//);
    private function renderJson($data){
        header('ContentType: application/json');
        echo json_encode(array(
            'json' => $data
        ));

        exit;
    }

  /**
   *  Called when controller/action output success json with array(
   *    json => array(
   *      success => 1, 
   *      data    => array(),
   *      message => 'OK', 
   *      code    => 'SUCCESS' // used for developer
   *    )
   *  ).
   *  @author Neil.zhou created at 20140623.
   *  
   *  @return void
   */
  protected function renderJsonWithSuccess($data = array(), $message = 'OK!', $status_code = 'SUCCESS') {
    return $this->renderJson(array(
      'success' => 1,
      'data'    => $data,
      'message' => $message,
      'code'    => $status_code,
      'timestamp' => microtime(true)
    ));
  }

  /**
   *  Called when controller/action output error josn.
   *  @author Neil.zhou created at 20140623.
   *  
   *  @param $message string
   *  @return void
   */
  protected function renderJsonWithError($message, $status_code = 'ERROR', $data = array()) {
    return $this->renderJson(array(
      'success' => 0,
      'data'    => $data,
      'message' => $message,
      'code'    => $status_code,
      'timestamp' => microtime(true)
    ));
  }
}
