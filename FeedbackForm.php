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
use Yii;
use yii\base\Widget;
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

	/** @var bool|string имя поля для JS-капчи или FALSE если не нужно */
	public $jsCaptchaName = false;

	/** @var string сообщение, которое отобразится в качестве ошибки первого поля если проверка капчи провалится. Используется только если $jsCaptchaName != false */
	public $captchFailMessage = 'Мы не можем отправить эту форму, т.к. есть подозрение, что Вы не являетесь человеком.';

	/** @var string надпись на кнопке отправки */
	public $sendButtonLabel = 'Отправить';

	/** @var string HTML-сообщение, которое получает юзер вместо формы при успешной отправке сообщения */
	public $okMessage = 'Ваше сообщение успешно отправлено.';

	/** @var bool показать ли повторно форму после отправки. TRUE - будет показана форма и ниже текст $okMessage, FALSE - будет выведен только текст $okMessage */
	public $showFormAfterSent = false;

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

    /**
     * @var bool включить валидацию на стороне клиента. ВНИМАНИЕ!!! Форма начинает глючить, если одновременно включена
     *      проверка капчи и валидация на клиенте. Лучше отключить валидацию и обернуть форму внутрь Pjax.
     */
	public $enableClientValidation = false;

	/** @var bool включить валидацию через AJAX. Не рекомендуется к использованию. Лучше отключить валидацию и обернуть форму внутрь Pjax. */
	public $enableAjaxValidation = false;

	/** @var bool Обернуть ли форму в Pjax (для обновления без перезагрузки) */
	public $enablePjax = false;

	public function run()
	{
		$secretIdFieldName = 'fm_'.md5($this->magicWord.'==='.$this->id);

		if($this->messageTemplate === false){
			$this->messageTemplate = '
<h2>Здравствуйте.</h2>
<p>Новое сообщение было отправлено со страницы <a href="'.Url::to(array_merge([''], $_GET), true).'">'.Url::to(array_merge([''], $_GET), true).'</a>.</p>
<p>Тема: <b>'.Html::encode($this->subject).'</b></p>
{text}
<p><br/>---<br/>Sent by Feedback robot at '.date('d.m.Y H:i:s').'</p>';
		}

		$oldTime = Yii::$app->session->get($this->sessionVarName, 0);

		if(time() - $this->blockDelay >= $oldTime)
		{
			$model = new FeedbackModel([
				'_inputs' => $this->inputs,
				'jsCaptchaName' => $this->jsCaptchaName,
				'captchFailMessage' => $this->captchFailMessage,
				'magicWord' => $this->magicWord,
			]);

			if($this->enablePjax){
				$this->htmlOptions['data-pjax'] = 1; // чтобы форма корректно работала с Pjax
			}

			if(Yii::$app->request->post($secretIdFieldName) == $this->id && $model->load(Yii::$app->request->post()))
			{
			    if(!empty($this->jsCaptchaName))
                {
                    $model->{$this->jsCaptchaName} = Yii::$app->request->post($model->getFullFieldName($this->jsCaptchaName, $this));
                    $model->{$this->jsCaptchaName.'1'} = Yii::$app->request->post($model->getFullFieldName($this->jsCaptchaName.'1', $this));
                }

				if($model->validate())
				{
					$senderEmail = $this->senderEmail;
					if($this->fieldSenderEmail !== false)
						$senderEmail = $model->{$this->fieldSenderEmail};

					foreach($this->toEmails as $email)
						$model->send($this->subject, $email, $senderEmail, $this->messageTemplate);

					$model->reset();

					Yii::$app->session->set($this->sessionVarName, time());

					if($this->showFormAfterSent)
						return $this->render('form', ['model' => $model, 'secretIdFieldName' => $secretIdFieldName, 'showSentMessage' => true]);
					else
						return $this->okMessage;
				}
			}

			echo $this->render('form', ['model' => $model, 'secretIdFieldName' => $secretIdFieldName, 'showSentMessage' => false]);
		}
		else
		{
			echo $this->blockMessage;
		}
	}
}