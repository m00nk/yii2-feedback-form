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
	const TYPE_CAPTCHA = 'captcha';
	const TYPE_CAPTCHA_CODE = 'captcha code';

	public $magicWord = '';

	public $jsCaptchaName = false; // имя поля или FALSE если не нужно

	public $_inputs = [];
//-----------------------------------------
// ПРИМЕР ИСПОЛЬЗОВАНИЯ (ФОРМАТ)
//-----------------------------------------
//		[
//			'label' => 'Мыло',
//			'field' => 'email',
//			'type' => self::TYPE_INPUT,
//          'hint' => 'this is the hint',
//			'rules' => [
//				['required'],
//				['email'],
//				['string', 'max' => 20]
//			],
//			'htmlOptions' => [],
//		],
//
//		[
//			'label' => 'Ваш пол',
//			'field' => 'sex',
//			'type' => self::TYPE_DROPDOWN,
//			'values' => [
//				'male' => 'Мужской',
//				'female' => 'Женский',
//			],
//			'rules' => [
//	            // правило 'IN' для списков подставляется автоматически
//				['required']
//			],
//		],
//
//		array(
//			'label' => 'Комментарий',
//			'field' => 'comment',
//			'type' => self::TYPE_TEXT,
//			'rules' => [
//				['required'],
//			],
//			'htmlOptions' => ['class' => 'span6', 'style' => 'height: 100px; resize: none;'],
//		],

	//-----------------------------------------
	private $_attrs = [];

	private $_captchaCode = 0;
	private $_captchaExpression = '';

	public function init()
	{
		parent::init();

		$this->_captchaCode = rand(100000, 999999);

		if($this->jsCaptchaName !== false)
		{
			$this->_inputs[] = [
				'label' => '',
				'field' => $this->jsCaptchaName,
				'type' => self::TYPE_CAPTCHA,
			];

			$this->_inputs[] = [
				'label' => '',
				'field' => $this->jsCaptchaName.'1',
				'type' => self::TYPE_CAPTCHA_CODE,
			];
		}

		foreach($this->_inputs as $inp)
		{
			if($inp['type'] == self::TYPE_CAPTCHA_CODE)
				$this->{$inp['field']} = $this->_getCodeByValue(eval('return '.$this->getExpression().';'));
			else
				$this->{$inp['field']} = '';
		}
	}

	public function rules()
	{
		$out = [];
		foreach($this->_inputs as $inp)
		{
			for($j = 0, $_cc = count($inp['rules']); $j < $_cc; $j++)
			{
				$rule = $inp['rules'][$j];
				$out[] = array_merge([$inp['field']], $rule);
			}

			if($inp['type'] == self::TYPE_DROPDOWN)
				$out[] = array_merge([$inp['field']], ['in', 'range' => array_keys($inp['values'])]);
		}

		if($this->jsCaptchaName !== false)
		{
			$out[] = [$this->jsCaptchaName.'1', 'safe'];

			$out[] = [
				$this->jsCaptchaName,
				function (){
					$oldVal = $this->{$this->jsCaptchaName.'1'};
					$newVal = $this->{$this->jsCaptchaName};
					$code = $this->_getCodeByValue($newVal);

					if($oldVal != $code)
						$this->addError($this->_inputs[0]['field'], 'You can not use this form because we are not sure you are human.');
				},
			];
		}

		return $out;
	}

	public function attributeLabels()
	{
		$out = [];
		foreach($this->_inputs as $inp)
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

	public function send($subject, $to, $from, $template)
	{
		$msg = '';
		for($i = 0, $_c = count($this->_inputs); $i < $_c; $i++)
		{
			$inp = $this->_inputs[$i];

			if($inp['type'] == self::TYPE_CAPTCHA || $inp['type'] == self::TYPE_CAPTCHA_CODE)
				continue;

			$value = $this->{$inp['field']};

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

	private function _getCodeByValue($val)
	{
		$_ = substr(md5($this->magicWord.'-=-=-=-'.$val), 10, 18);

		return $_;
	}

	public function getExpression()
	{
		if(empty($this->_captchaExpression))
		{
			$a = rand(10000, 99999);
			$b = rand(10000, 99999);
			$this->_captchaExpression = $this->_captchaCode.' + '.$a.' - '.$b;
		}

		return $this->_captchaExpression;
	}

	public function resetCaptcha()
	{
		$this->_captchaExpression = '';

		$this->{$this->jsCaptchaName} = '';
		$this->{$this->jsCaptchaName.'1'} = $this->_getCodeByValue(eval('return '.$this->getExpression().';'));
	}
}