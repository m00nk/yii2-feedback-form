<?php
/**
 * @copyright (C) FIT-Media.com {@link http://fit-media.com}
 * Date: 07.10.15, Time: 0:55
 *
 * @author        Dmitrij "m00nk" Sheremetjev <m00nk1975@gmail.com>
 * @package
 */

namespace m00nk\feedbackForm\models;

use yii\base\Model;
use yii\helpers\Html;

class FeedbackModel extends Model
{
	const TYPE_INPUT = 'input';
	const TYPE_TEXT = 'text';
	const TYPE_DROPDOWN = 'dropdown';

	public $_inputs = array(
//-----------------------------------------
// ПРИМЕР ИСПОЛЬЗОВАНИЯ (ФОРМАТ)
//-----------------------------------------
//		array(
//			'label' => 'Мыло',
//			'field' => 'email',
//			'type' => self::TYPE_INPUT,
//			'rules' => array(
//				array('required'),
//				array('email'),
//				array('length', 'max' => 20)
//			),
//			'htmlOptions' => array(),
//		),
//
//		array(
//			'label' => 'Ваш пол',
//			'field' => 'sex',
//			'type' => self::TYPE_DROPDOWN,
//			'values' => array(
//				'male' => 'Мужской',
//				'female' => 'Женский',
//			),
//			'rules' => array(
//	            // правило 'IN' для списков подставляется автоматически
//				array('required')
//			),
//		),
//
//		array(
//			'label' => 'Комментарий',
//			'field' => 'comment',
//			'type' => FeedbackForm::TYPE_TEXT,
//			'rules' => array(
//				array('required'),
//			),
//			'htmlOptions' => array('class' => 'span6', 'style' => 'height: 100px; resize: none;'),
//		),
	);

	//-----------------------------------------
	private $_attrs = array();

	public function init()
	{
		parent::init();

		foreach($this->_inputs as $inp)
			$this->{$inp['field']} = '';
	}

	public function rules()
	{
		$out = array();
		foreach ($this->_inputs as $inp)
		{
			for ($j = 0, $_cc = count($inp['rules']); $j < $_cc; $j++)
			{
				$rule = $inp['rules'][$j];
				$out[] = array_merge(array($inp['field']), $rule);
			}

			if($inp['type'] == self::TYPE_DROPDOWN)
				$out[] = array_merge(array($inp['field']), array('in', 'range' => array_keys($inp['values'])));
		}
		return $out;
	}

	public function attributeLabels()
	{
		$out = array();
		foreach ($this->_inputs as $inp)
			$out[$inp['field']] = $inp['label'];

		return $out;
	}

	public function __set($var, $value)
	{
		$this->_attrs[$var] = $value;
	}

	public function __get($var)
	{
		if(array_key_exists($var, $this->_attrs)) return $this->_attrs[$var];
		throw new \Exception("Variable '$var' is absent");
	}


//	public function fillAttributes($attrs)
//	{
//		$this->_fillAttrs();
//
//		foreach ($attrs as $k => $v)
//			$this->$k = $v;
//	}

	public function send($subject, $to, $from, $template)
	{
		$msg = '';
		for ($i = 0, $_c = count($this->_inputs); $i < $_c; $i++)
		{
			$inp = $this->_inputs[$i];
			$var = $inp['field'];

			$value = $this->$var;

			if($inp['type'] == self::TYPE_DROPDOWN)
				$value = $inp['values'][$value];

			$msg[] = Html::tag('dl',
				Html::tag('dt', $inp['label']).
				Html::tag('dd', $value)
			);
		}
		$msg = implode('', $msg);

		$msg = str_replace('{text}', $msg, $template);

		\Yii::$app->mailer->compose()
			->setFrom($from)
			->setTo($to)
			->setSubject($subject)
			->setHtmlBody($msg)
			->send();
	}

}