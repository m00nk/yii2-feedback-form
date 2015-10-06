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
use yii\helpers\Url;

class FeedbackForm extends Widget
{
	public $toEmails = [];

	/** @var string мыло отправителя (робота). Используется только если параметр fieldSenderEmail == false */
	public $senderEmail = '';

	/** @var bool в качестве обратного адреса можно использовать значение поля. В этом случае данный параметр должен содержать название поля. */
	public $fieldSenderEmail = false;

	/** @var string тема письма */
	public $subject = '';

	/** @var array формат данных:
	 * <pre>
	 * array(
	 *   array(
	 *     'label' => 'Мыло',
	 *     'field' => 'email',
	 *     'type' => 'input', // FeedbackForm::TYPE_INPUT
	 *     'rules' => array(
	 *         array('required'),
	 *         array('email'),
	 *         array('length', 'max' => 20)
	 *      ),
	 *      'htmlOptions' => array(),
	 *   ),
	 *
	 *   array(
	 *      'label' => 'Ваш пол',
	 *      'field' => 'sex',
	 *      'type' => 'dropdown', // FeedbackForm::TYPE_DROPDOWN
	 *      'values' => array(
	 *          'male' => 'Мужской',
	 *          'female' => 'Женский',
	 *      ),
	 *      'rules' => array(
	 *          // правило 'IN' для списков подставляется автоматически
	 *          array('required')
	 *      ),
	 *  ),
	 *
	 *  array(
	 *      'label' => 'Комментарий',
	 *      'field' => 'comment',
	 *      'type' => 'text', // FeedbackForm::TYPE_TEXT
	 *      'rules' => array(
	 *          array('required'),
	 *      ),
	 *      'htmlOptions' => array('class' => 'span6', 'style' => 'height: 100px; resize: none;'),
	 *  ),
	 * )
	 * </pre>
	 */
	public $inputs = array();

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
	public $htmlOptions = array();

	/** @var string тип формы ('vertical', 'inline', 'horizontal', 'search') */
	public $formLayout = 'horizontal';

	/** @var bool|string шаблон сообщения. */
	public $messageTemplate = false;

	/** @var bool|string текст заголовка формы или false если заголовок не нужен */
	public $legend = false;

	public function run()
	{
		if($this->messageTemplate === false)
			$this->messageTemplate = '
<h2>Здравствуйте.</h2>
<p>Новое сообщение было отправлено со страницы <a href="'.Url::to([''], true).'">'.Url::to([''], true).'</a>.</p>
{text}
<p><br/>---<br/>Sent by Feedback robot at '.date('d.m.Y H:i:s').'</p>';

		$oldTime = Yii::$app->session->get($this->sessionVarName, 0);
		if(time() - $oldTime > $this->blockDelay)
		{
			$model = new FeedbackModel();
			$model->_inputs = $this->inputs;

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

			echo $this->render('form', ['model' => $model]);
		}
		else
		{
			echo $this->blockMessage;
		}
	}
}