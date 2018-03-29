<?php
/**
 * @copyright (C) FIT-Media.com {@link http://fit-media.com}
 * Date: 07.10.15, Time: 0:51
 *
 * @author        Dmitrij "m00nk" Sheremetjev <m00nk1975@gmail.com>
 * @package
 */

namespace m00nk\feedbackForm;

use m00nk\feedbackForm\models\FeedbackModel;
use yii\base\Widget;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

class FeedbackForm extends Widget
{
	public $toEmails = [];

	/** @var string кодовое слово для генерации уникальных значений */
	public $magicWord = '';

	/** @var string мыло отправителя (робота). Используется только если параметр fieldSenderEmail == false */
	public $senderEmail = '';

	/** @var bool в качестве обратного адреса можно использовать значение поля. В этом случае данный параметр должен содержать название поля. */
	public $fieldSenderEmail = false;

	/** @var string тема письма */
	public $subject = '';

	/** @var array формат данных:
	 * <pre>
	 * [
	 *   [
	 *     'label' => 'Мыло',
	 *     'field' => 'email',
	 *     'type' => FeedbackForm::TYPE_INPUT,
	 *     'hint' => 'This is hint',
	 *     'rules' => [
	 *         ['required', 'message' => 'You have to fill this field'],
	 *         ['email'],
	 *         ['string', 'max' => 20]
	 *      ],
	 *      'htmlOptions' => [],
	 *   ],
	 *
	 *   [
	 *      'label' => 'Ваш пол',
	 *      'field' => 'sex',
	 *      'type' => FeedbackForm::TYPE_DROPDOWN
	 *      'values' => [
	 *          'male' => 'Мужской', // ключи можно не задавать
	 *          'female' => 'Женский',
	 *      ],
	 *      'rules' => [
	 *          // правило 'IN' для списков подставляется автоматически
	 *          ['required']
	 *      ],
	 *  ],
	 *
	 *  [
	 *      'label' => 'Комментарий',
	 *      'field' => 'comment',
	 *      'type' => FeedbackForm::TYPE_TEXT
	 *      'rules' => [
	 *          ['required'],
	 *      ],
	 *      'htmlOptions' => ['class' => 'span6', 'style' => 'height: 100px; resize: none;'],
	 *   ],
	 * ]
	 * </pre>
	 */
	public $inputs = [];

	/** @var bool|string имя поля для JS-капчи или FALSE если не нужно  */
	public $jsCaptchaName = false;

	/** @var string надпись на кнопке отправки */
	public $sendButtonLabel = 'Отправить';

	/** @var string HTML-сообщение, которое получает юзер вместо формы при успешной отправке сообщения */
	public $okMessage = 'Ваше сообщение успешно отправлено.';

	/** @var string имя переменной в сессии, которая блокирует повторную отправку сообщений для данной формы */
	public $sessionVarName = 'feedback_form_blocked';

	/** @var int длительность промежутка между отправками сообщений в секундах (антифлуд) */
	public $blockDelay = 300;

	/** @var string HTML-сообщение, которое увидит пользователь при попытке повторной отправки сообщения */
	public $blockMessage = 'Вы сможете отправить следующее сообщение не ранее чем через 5 минут.';

	/** @var array атрибуты формы */
	public $htmlOptions = [];

	/** @var string тип формы ('default', 'inline', 'horizontal') http://www.yiiframework.com/doc-2.0/yii-bootstrap-activeform.html */
	public $formLayout = 'horizontal';

	/** @var array конфигурация полей формы http://www.yiiframework.com/doc-2.0/yii-bootstrap-activeform.html */
	public $fieldConfig = [];

	/** @var string классы, которые будут прописаны у DIV'а с кнопкой отправки формы */
	public $submitDivClasses = 'col-sm-6 col-sm-offset-3';

	/** @var bool|string шаблон сообщения. */
	public $messageTemplate = false;

	/** @var bool|string текст заголовка формы или false если заголовок не нужен */
	public $legend = false;

	/** @var bool включить валидацию на стороне клиента */
	public $enableClientValidation = false;

	/** @var bool включить валидацию через AJAX */
	public $enableAjaxValidation = false;

	public function run()
	{
		if($this->messageTemplate === false)
			$this->messageTemplate = '
<h2>Здравствуйте.</h2>
<p>Новое сообщение было отправлено со страницы <a href="'.Url::to(array_merge([''], $_GET), true).'">'.Url::to(array_merge([''], $_GET), true).'</a>.</p>
<p>Тема: <b>'.Html::encode($this->subject).'</b></p>
{text}
<p><br/>---<br/>Sent by Feedback robot at '.date('d.m.Y H:i:s').'</p>';

		$oldTime = Yii::$app->session->get($this->sessionVarName, 0);
		if(time() - $oldTime > $this->blockDelay)
		{
			$model = new FeedbackModel([
				'_inputs' => $this->inputs,
				'jsCaptchaName' => $this->jsCaptchaName,
				'magicWord' => $this->magicWord,
			]);

			if($model->load(Yii::$app->request->post()))
			{
				if($model->validate())
				{
					$senderEmail = $this->senderEmail;
					if($this->fieldSenderEmail !== false)
						$senderEmail = $model->{$this->fieldSenderEmail};

					foreach($this->toEmails as $email)
						$model->send($this->subject, $email, $senderEmail, $this->messageTemplate);

					echo $this->okMessage;

					Yii::$app->session->set($this->sessionVarName, time() + $this->blockDelay);
					return;
				}
			}

			$model->resetCaptcha();
			echo $this->render('form', ['model' => $model]);
		}
		else
		{
			echo $this->blockMessage;
		}
	}
}