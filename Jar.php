<?php

/**
 * Creates Json Ajax Responses (JAR) using the JSend format
 *
 * @author Wil Wade <wil@supplyhog.com>
 * @copyright (c) 2012, Wil Wade
 * @license MIT
 * @category Extensions.Yii
 * @version 1.0
 *
 * Standardizes JSON responses using the Jsend (http://labs.omniti.com/labs/jsend) format.
 *
 * JSend Summary:
 * Type			Description																								Required Keys		Optional Keys
 * success		All went well, and (usually) some data was returned.													status, data
 * fail			There was a problem with the data submitted, or some pre-condition of the API call wasn't satisfied		status, data
 * error		An error occurred in processing the request, i.e. an exception was thrown								status, message		code, data
 *
 * Example Success Response:
 * {
 *  status : "success",
 *  data : {
 *      "posts" : [
 *          { "id" : 1, "title" : "A blog post", "body" : "Some useful content" },
 *          { "id" : 2, "title" : "Another blog post", "body" : "More content" },
 *      ]
 *   }
 * }
 *
 * Example Error Response:
 * {
 *  "status" : "error",
 *  "message" : "A title is required"
 * }
 *
 */
class Jar extends CApplicationComponent{

	/**
	 * Status response
	 * success, fail, error
	 * @var string
	 */
	private $_status = 'success';

	/**
	 * Message explaining the error. Only for errors.
	 * @var string
	 */
	private $_message = '';

	/**
	 * Any type of application data. Often models
	 * @var array
	 */
	private $_data = array();

	/**
	 * Error code, if any.
	 * @var string
	 */
	private $_code = '';

	/**
	 * Set the error.
	 * @param string $message
	 * @param string $code
	 * @return Jar
	 */
	public function error($message, $code = ''){
		$this->_status = 'error';
		$this->_message = $message;
		$this->_code = $code;
		return $this;
	}

	/**
	 * Set the fail.
	 *
	 * @return Jar
	 */
	public function fail(){
		$this->_status = 'fail';
		return $this;
	}

	/**
	 * Is an error response set?
	 *
	 * @return bool
	 */
	public function hasError(){
		return $this->_status === 'error';
	}

	/**
	 * Is a fail response set?
	 *
	 * @return bool
	 */
	public function hasFail(){
		return $this->_status === 'fail';
	}

	/**
	 * Is a success response set?
	 *
	 * @return bool
	 */
	public function hasSuccess(){
		return $this->_status === 'success';
	}

	/**
	 * Reset the Response
	 *
	 * @return Jar
	 */
	public function reset(){
		$this->_status = 'success';
		$this->_data = array();
		$this->_message = '';
		$this->_code = '';
		return $this;
	}

	/**
	 * Add a model to the response.
	 * Allows for 1:1 relations, including nested using dot notation in the attributes array
	 * Example: relation.attribute, relation.relation.attribute
	 *
	 * @param mixed $model
	 * @param array $attributes
	 * @return Jar
	 * @throws Exception
	 */
	public function addModel($model, array $attributes = array()){
		$class = get_class($model);
		if(!isset($this->_data[$class])){
			$this->_data[$class] = array();
		}

		if(empty($attributes)){
			$this->_data[$class][] = $model->attributes;
		}
		else{
			$addToData = array();
			foreach($attributes as $attribute){
				$value = NULL;
				$with = NULL;
				if(strpos($attribute, '.') !== FALSE){
					$with = explode('.', $attribute);
					$finalKey = array_pop($with);
					$relation = $model;
					foreach($with as $key){
						//Note that here we only allow for 1:1 relations
						if(isset($relation->{$key}) && !is_array($relation->{$key})){
							$relation = $relation->{$key};
						}
						else{
							//Use NULL
							$relation = NULL;
							break;
						}
					}
					if(!is_null($relation) && isset($relation->{$finalKey})){
						$value = $relation->{$finalKey};
					}
				}
				else if(isset($model->{$attribute})){
					$value = $model->{$attribute};
				}

				if(is_array($with)){
					$building =& $addToData;
					for($i = 0; count($with) > $i; $i++){
						if(!isset($building[$with[$i]])){
							$building[$with[$i]] = array();
						}
						$building =& $building[$with[$i]];
					}
					$building[$finalKey] = $value;
				}
				else{
					$addToData[$attribute] = $value;
				}
			}
			if(empty($addToData)){
				throw new Exception('Tried to add an empty model to the Jar data response.');
			}
			$this->_data[$class][] = $addToData;
		}
		return $this;
	}

	/**
	 * Add an array of models to the Response
	 * @param array $models
	 * @param array $attributes
	 * @return Jar
	 */
	public function addModels(array $models, array $attributes = array()){
		foreach($models as $model){
			$this->addModel($model, $attributes);
		}
		return $this;
	}

	/**
	 * Take a CDataProvider and add the current data to the data array
	 *
	 * Note that Attributes only work for CActiveDataProvider
	 *
	 * @param CDataProvider $dp
	 * @param array $attributes
	 * @return Jar
	 */
	public function addDataProvider($dp, array $attributes = array()){
		switch(get_class($dp)){
			case 'CActiveDataProvider':
				$this->addModels($dp->getData(), $attributes);
				break;

			default:
				if(!isset($this->_data['rows'])){
					$this->_data['rows'] = $dp->getData();
				}
				else{
					$this->_data['rows'] = array_merge($this->_data['rows'], $dp->getData());
				}

				break;
		}
		return $this;
	}

	/**
	 * Add a key value to the
	 * @param string $key
	 * @param mixed $value
	 * @return Jar
	 */
	public function addData($key, $value){
		$this->_data[$key] = $value;
		return $this;
	}

	/**
	 * Get the data array.
	 *
	 * @return array
	 */
	public function getData(){
		return $this->_data;
	}

	/**
	 * Remove a key from the data response
	 * @param string $key
	 * @return Jar
	 */
	public function unsetData($key){
		if(isset($this->_data[$key]))
			unset($this->_data[$key]);
		return $this;
	}

	/**
	 * Get the JSON for the response
	 * @return json
	 */
	public function getJson(){
		switch($this->_status){
			case 'success':
			case 'fail':
				return CJSON::encode(array('status' => $this->_status, 'data' => $this->_data,));
			default:
				return CJSON::encode(array('status' => $this->_status, 'message' => $this->_message, 'code' => $this->_code, 'data' => $this->_data,));
		}
	}

	/**
	 * Send that response to the user
	 */
	public function send(){
		header('Content-type: application/json');
		echo $this->getJson();
		Yii::app()->end();
	}
}