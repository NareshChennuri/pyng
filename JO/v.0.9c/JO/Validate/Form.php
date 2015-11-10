<?php

class JO_Validate_Form {
	//Глобални променливи
	private $validator_error_message	= '';
	private $validator_error_messages	= array();
	private $validator_tag_open			= '';
	private $validator_tag_close		= '';
	private $validator_error_flag		= false;
	private $validator_validform_flag	= true;
	private $break_err					= false;
	private $err						= false;

	//Конструктор
	function __construct($tag_open = '', $tag_close = '', $no_break_err = true)
	{
		$this->validator_tag_open		= $tag_open;
		$this->validator_tag_close		= $tag_close;
		$this->break_err 				= $no_break_err;
	}

	function __destruct()
	{
		//Унишожаваме променливи на класа
		unset($this->validator_error_message, $this->validator_error_messages, 
			  $this->validator_tag_open, $this->validator_tag_close, $this->validator_error_flag,
			  $this->validator_validform_flag
  		);
	}

	/*Делимитер между филтрите ';'
	* Достъпни филтри: max_length[N], min_length[N], not_empty, abc, number, num_abc, email, domain, reg_exp
	*/
	public function _set_rules($component_name = '', $component_title = '', $filter = '')
	{
		//Локални променливи
		$right_side	= '';
		$param		= '';

		//if (!empty($component_name) && !empty($filter) && 
		//	is_string($component_name) && is_string($filter)
		//)
		if (!empty($filter) && is_string($filter) && !empty($component_title) && is_string($component_title))
		{
			$filter	= $this->explode_filter($filter);

			//Извличаме стойности на параметрите заключени в []
			for ($i = 0; $i<count($filter); $i++)
			{
				
				//Проверяваме на филтър 'not_empty'		-	не е празен
				if (strncmp('not_empty', $filter[$i], 9) == 0 && ($this->break_err || !$this->err))
				{
					//if (empty($_POST[$component_name]))
					if (empty($component_name)) 
					{
						//Установяваме флаг
						$this->validator_error_flag 	= true;
						$this->validator_validform_flag = false;

						$this->err = true;

						//Формираме съобщение за грешка
						$this->_set_error_messages('not_empty', $component_title);
					}
				}

                if(strncmp('error', $filter[$i], 5) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err)) {
                    if($component_name) {
                        
                        $this->validator_error_flag     = true;
                        $this->validator_validform_flag = false;

                        $this->err = true;

                        //Формираме съобщение за грешка
                        $this->_set_error_messages('error', $component_title);   
                    }
                }
                
				if (strncmp('reg_exp', $filter[$i], 7) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err))
                {
                    preg_match('/^reg_exp\[(.*)\]+$/', $filter[$i], $match);
                    if(isset($match[1])) {
                        if (!preg_match($match[1], $component_name))
                        {
                            
                            $this->validator_error_flag     = true;
                            $this->validator_validform_flag = false;

                            $this->err = true;

                            //Формираме съобщение за грешка
                            $this->_set_error_messages('reg_exp', $component_title);
                        }  
                    }
                }

				if (strncmp('domain', $filter[$i], 6) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err))
				{
					if (!preg_match("|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i", $component_name))
					{
                        
                        $this->validator_error_flag     = true;
						$this->validator_validform_flag = false;

						$this->err = true;

						//Формираме съобщение за грешка
						$this->_set_error_messages('domain', $component_title);
					}
				}

				//Проверяваме на филтър 'email'		-	валиден е-маил
				if (strncmp('email', $filter[$i], 5) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err))
				{
					//if (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $_POST[$component_name]))
					if (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $component_name))
					{
                        
                        $this->validator_error_flag     = true;
						$this->validator_validform_flag = false;

						$this->err = true;

						//Формираме съобщение за грешка
						$this->_set_error_messages('email', $component_title);
					}
				}

				//Проверяваме на филтър 'price'	-	 цена
				if (strncmp('price', $filter[$i], 5) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err))
				{
					//if (!preg_match("/^[a-z0-9]+$/i", $_POST[$component_name]))
					if (!preg_match("/^([0-9]{1,})(.([0-9]{1,}))?+$/iu", $component_name))
					{
                        
                        $this->validator_error_flag     = true;
						$this->validator_validform_flag = false;

						$this->err = true;

						//Формираме съобщение за грешка
						$this->_set_error_messages('price', $component_title);
					}
				}

				//Проверяваме на филтър 'num_abc'	-	 латиница и цифри
				if (strncmp('num_abc', $filter[$i], 7) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err))
				{
					//if (!preg_match("/^[a-z0-9]+$/i", $_POST[$component_name]))
					if (!preg_match("/^[a-z0-9\,\.\ ]+$/iu", $component_name))
					{
                        
                        $this->validator_error_flag     = true;
						$this->validator_validform_flag = false;

						$this->err = true;

						//Формираме съобщение за грешка
						$this->_set_error_messages('num_abc', $component_title);
					}
				}

				//Проверяваме на филтър 'num_abc'	-	 латиница и цифри
				if (strncmp('username', $filter[$i], 8) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err))
				{
					//if (!preg_match("/^[a-z0-9]+$/i", $_POST[$component_name]))
					if (!preg_match("/^[a-z0-9\_\.]+$/i", $component_name))
					{
                        
                        $this->validator_error_flag     = true;
						$this->validator_validform_flag = false;

						$this->err = true;

						//Формираме съобщение за грешка
						$this->_set_error_messages('username', $component_title);
					}
				}

				//Проверяваме на филтър 'abc'	-	само латиница
				if (strncmp('abc', $filter[$i], 3) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err))
				{
					//if (!preg_match("/^[a-z]+$/i", $_POST[$component_name]))
					if (!preg_match("/^[a-z\,\.\ ]+$/iu", $component_name))
					{
                        
                        $this->validator_error_flag     = true;
						$this->validator_validform_flag = false;

						$this->err = true;

						//Формираме съобщение за грешка
						$this->_set_error_messages('abc', $component_title);
					}
				}

				//Проверяваме на филтър 'number'	-	само цифри
				if (strncmp('number', $filter[$i], 6) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err))
				{
					//if (!preg_match("/^[0-9]+$/", $_POST[$component_name]))
					if (!preg_match("/^[0-9]+$/", $component_name))
					{
                        
                        $this->validator_error_flag     = true;
						$this->validator_validform_flag = false;

						$this->err = true;

						//Формираме съобщение за грешка
						$this->_set_error_messages('number', $component_title);
					}
				}

				//Проверяваме на филтър 'date'	-	само дата yyyy-mm-dd
				if (strncmp('date', $filter[$i], 4) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err))
				{
					//if (!preg_match("/^[0-9]+$/", $_POST[$component_name]))
					if ( preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})+$/", $component_name, $match)) { 
						if( !@checkdate($match[2], $match[3], $match[1]) ) {
							$this->validator_error_flag     = true;
							$this->validator_validform_flag = false;
	
							$this->err = true;
	
							//Формираме съобщение за грешка
							$this->_set_error_messages('date', $component_title);
						}
						
					} else {
						$this->validator_error_flag     = true;
						$this->validator_validform_flag = false;

						$this->err = true;

						//Формираме съобщение за грешка
						$this->_set_error_messages('date', $component_title);
					}
				}

				//Намираме дали в даденния филтр исползва се параметър
				if (strpos($filter[$i],'[') == true)
				{
					//Взимаме стойности заградени в скобите '[]'
					$right_side = strstr($filter[$i], '[');

					//Взимаме самия параметър
					list($param) = sscanf($right_side, "[%d]");

					//Прилагаме валидация към елементи на формата
					//Сравняваме лявата част на филтъра
					if (strncmp('min_length', $filter[$i], 10) == 0 && $this->validator_error_flag == false && ($this->break_err || !$this->err))
					{
						//echo $param;
						//if (isset($_POST[$component_name]) && strlen($_POST[$component_name]) < $param)
						//if (strlen($_POST[$component_name]) < $param)
						if (mb_strlen($component_name, 'utf-8') < $param)
						{
                            
                            $this->validator_error_flag     = true;
							$this->validator_validform_flag = false;
							
							$this->err = true;

							//Формираме съобщение за грешка
							$this->_set_error_messages('min_length', $component_title, $param);
						}
					}

					if (strncmp('max_length', $filter[$i], 10) == 0 && ($this->break_err || !$this->err))
					{
						//if (isset($_POST[$component_name]) && strlen($_POST[$component_name]) > $param)
						//if (isset($component_name) && strlen($component_name) > $param)
						if (mb_strlen($component_name, 'utf-8') > $param)
						{
                            
                            $this->validator_error_flag     = true;
							$this->validator_validform_flag = false;

							$this->err = true;

							//Формираме съобщение за грешка
							$this->_set_error_messages('max_length', $component_title, $param);
						}
					}

					if (strncmp('count', $filter[$i], 5) == 0 && ($this->break_err || !$this->err))
					{
						//if (isset($_POST[$component_name]) && strlen($_POST[$component_name]) > $param)
						//if (isset($component_name) && strlen($component_name) > $param)
						if (count($component_name) < $param)
						{
                            
                            $this->validator_error_flag     = true;
							$this->validator_validform_flag = false;

							$this->err = true;

							//Формираме съобщение за грешка
							$this->_set_error_messages('count', $component_title, $param);
						}
					}

					//Нулираме променливи
					$right_side = '';
					$param 		= '';
				}
			}

			//Унищожаваме всички локални променливи
			unset($filter, $component_name, $component_title, $right_side, $param);
		}
		else
		{
			return false;
		}
        $this->validator_error_flag     = false;
	}

	//Функция разбива филтъра според delimiter ';'
	private function explode_filter($filter = '')
	{
		return explode(';', $filter);
	}

	public function _set_errors($filter = '', $error_message = '')
	{
		if (!empty($filter) && !empty($error_message) &&  is_string($error_message) ) {
			
			$this->validator_error_messages[$filter] = $error_message;

			//Унищожаваме променливи
			unset($filter, $error_message);
		}
	}

	//Функция формира грешката
	private function _set_error_messages($filter = '', $component_title = '', $param = '')
	{
		//Локални променливи
		$key = 0;

		if (!empty($filter) && !empty($component_title))
		{ 
			//Намираме елемент в масива
			if(isset($this->validator_error_messages[$filter])) {
				$this->validator_error_message .= str_replace(array('{field}','{symbols}'), array($component_title, $param), $this->validator_error_messages[$filter]) . '<br />';
			}

			//Унищожаваме променливи
			unset($filter, $component_title, $key);
		}
	}

	//Функция връща грешката
	public function _get_error_messages()
	{
		return $this->validator_tag_open . $this->validator_error_message . $this->validator_tag_close;
	}

	public function _valid_form()
	{
		return $this->validator_validform_flag;
	}

	public function _set_form_errors($error_message = '')
	{
		$this->validator_error_message .= $error_message . '<br />';
	}

	public function _set_valid_form($param = true)
	{
		$this->validator_validform_flag = $param;
	}
}

/*Използване:
* Създаваме обект, като предаваме в конструктора отварящ се и затварящ се таг в които ще бъде заключена грешка 
* $a = new Form_validator('<div style="color: red;">', '</div>', false); //false - break errors, true - no break errors
*
* Задаваме всички възможни варианти на грешките, къде първия параметър е името на филтъра, втория и третия параметър съобщение за грешка:
* $a ->_set_errors('not_empty', 	'Поле ', ' не трябва да бъде празно');
* $a ->_set_errors('max_length', 	'Поле ', ' трябва да съдържи не повече от %d символа');
* $a ->_set_errors('min_length', 	'Поле ', ' трябва да съдържи не по-малко от %d символа');
* $a ->_set_errors('abc', 			'Поле ', ' трябва да се съдържи само букви');
* $a ->_set_errors('number', 		'Поле ', ' трябва да се съдържи само цифри');
* $a ->_set_errors('num_abc',		'Поле ', ' трябва да се съдържи цифри и букви');
* $a ->_set_errors('email', 		'Поле ', ' трябва да се съдържи валиден ймаил адрес');
*
* Установяваме правила за филтрация на елемент от формата, 1-вия параметър името на полето за проверката, втория параметър заглавие на полето, третия параметър - филтър (елементите на
* филтъра трябва да бъдат разделени със символ ';'). Елементите на филтъра се исползват в реда на срещането им, в дадения пример първо ще бъде извикан
* филтър not_empty, втория min_length и т.н. Филтър not_empty е с предимство и винаги трябва да бъде в началото.
* $a ->_set_rules('name', 'NAME'			'not_empty;min_length[2];max_length[15];email');
* 
* Проверяваме дали данните са изпратени и формата е валидна
* if (!$a->_valid_form() && isset($_POST['send'])) //send - името на бутона
* {
*	echo $a->_get_error_messages();		<--- ако формата не е валидна (има срещнати грешки) и данните са изпратени тогава извеждаме съобщения за грешки
* }
* 
* Унищожаваме обект
* unset($a);
*/

?>