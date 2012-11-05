<?php

class UQLRule{
	
	private $entity_name;
	private $alises_map;
	private $rules_map;

    public function __construct($entity_name)
	{
		$this->entity_name = $entity_name;
		$this->alises_map  = new UQLMap();
		$this->rules_map   = new UQLMap();
	}
	
	public function __call($function_name,$parameters)
	{
		$local_params_count = count($parameters);
		if($local_params_count == 0) return;
		
		$this->addRule($function_name, $parameters);
	}
	
	protected function addRule($field,$rule)
	{
		if(!$this->rules_map->isElementExist($field))
		 	$this->rules_map->addElement($field, new UQLMap());
		
		    $local_rule = $this->rules_map->findElement($field);
			$local_rule->addElement($local_rule->getCount(),$rule);
			$this->rules_map->addElement($field, $local_rule);
	}
	
	public function getRulesByFieldName($field_name)
	{
		return $this->rules_map->findElement($field_name);
	}
	
	public function addAlias($key, $value)
	{
		$this->alises_map->addElement($key, $value);
	} 
	
	public function getRules()
	{
		return $this->alises_map;
	}
	
	public function getEntityName()
	{
		return $this->entity_name;
	}
	
	public function getAliases()
	{
		return $this->alises_map;
	}
	
	public function __destruct()
	{
		$this->entity_name = null;
		$this->rules_map = null;
		$this->alises_map = null;
	}
}


// rules type :  IGNORE | STRICT

//function uql_rule_isemail($name,$value,$alias = null,&$result = null) {}
//function uql_rule_between($name,$value,$params = null,$alias = null,&$result = null) {}

//$the_students_rules = new UQLRule("studnets");

//$the_students_rules->id('number');
//$the_students_rules->name('length',50);
//$the_students_rules->email('email');
//$the_students_rules->name('between',10,50);


?>