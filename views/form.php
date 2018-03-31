<?php
/**
 * @copyright (C) FIT-Media.com {@link http://fit-media.com}
 * Date: 07.10.15, Time: 1:04
 *
 * @author Dmitrij "m00nk" Sheremetjev <m00nk1975@gmail.com>
 */

use \yii\web\View;
use \yii\helpers\Html;
use \yii\bootstrap\ActiveForm;
use m00nk\feedbackForm\models\FeedbackModel;
use m00nk\feedbackForm\FeedbackForm;

/**
 * @var $this  View
 * @var $model FeedbackModel
 * @var string $secretIdFieldName имя поля, содержащего уникальный ID данной формы. Необходимо, чтобы несколько форм на одной странице не ловили чужие данные.
 * @var string $showSentMessage нужно ли показать под формой сообщение об успешной отправке?
 *
 * @var $form ActiveForm
 * @var $widget FeedbackForm
 */

$widget = $this->context;

if($widget->jsCaptchaName !== false)
	\yii\web\JqueryAsset::register($this);

$form = ActiveForm::begin([
	'id' => $widget->id,
	'action' => false,
	'enableClientValidation' => $widget->enableClientValidation,
	'enableAjaxValidation' => $widget->enableAjaxValidation,

	'layout' => $widget->formLayout,
	'fieldConfig' => $widget->fieldConfig,

	'options' => $widget->htmlOptions,
]);

echo Html::hiddenInput($secretIdFieldName, $widget->id);

if($widget->legend !== false) echo Html::tag('legend', array(), $widget->legend);

foreach($model->_inputs as $inp)
{
	switch ($inp['type'])
	{
		case FeedbackModel::TYPE_CAPTCHA_CODE:
			echo Html::activeHiddenInput($model, $inp['field']);
			break;

		case FeedbackModel::TYPE_CAPTCHA:
			echo Html::activeHiddenInput($model, $inp['field']);
			$this->registerJs('$("#'.Html::getInputId($model, $inp['field']).'").val('.$model->getExpression().');');
			break;

		case FeedbackModel::TYPE_INPUT:
			echo $form->field($model, $inp['field'], ['inputOptions' => isset($inp['htmlOptions']) ? $inp['htmlOptions'] : []])
			->hint(!empty($inp['hint']) ? $inp['hint'] : '');
			break;

		case FeedbackModel::TYPE_TEXT:
			echo $form->field($model, $inp['field'], ['inputOptions' => isset($inp['htmlOptions']) ? $inp['htmlOptions'] : []])->textarea()
				->hint(!empty($inp['hint']) ? $inp['hint'] : '');
			break;

		case FeedbackModel::TYPE_DROPDOWN:
			echo $form->field($model, $inp['field'], ['inputOptions' => isset($inp['htmlOptions']) ? $inp['htmlOptions'] : []])->dropDownList($inp['values'])
				->hint(!empty($inp['hint']) ? $inp['hint'] : '');
			break;
	}
}

?>
	<div class="form-group">
		<div class="<?= $widget->submitDivClasses; ?>">
			<?= Html::submitButton($widget->sendButtonLabel, ['class' => 'btn btn-primary']); ?>
		</div>
		<div class="clearfix"></div>
	</div>

<?php

ActiveForm::end();

if($showSentMessage)
	echo Html::tag('div', $widget->okMessage, ['class' => 'form-group']);
