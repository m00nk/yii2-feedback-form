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
 * @var $form ActiveForm
 * @var $widget FeedbackForm
 */

$widget = $this->context;

if($widget->jsCaptchaName !== false)
	\yii\web\JqueryAsset::register($this);


$form = ActiveForm::begin([
	'enableClientValidation' => false,
	'enableAjaxValidation' => false,

	'layout' => $widget->formLayout,
	'options' => $widget->htmlOptions
]);

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
			echo $form->field($model, $inp['field'], ['inputOptions' => isset($inp['htmlOptions']) ? $inp['htmlOptions'] : []]);
			break;

		case FeedbackModel::TYPE_TEXT:
			echo $form->field($model, $inp['field'], ['inputOptions' => isset($inp['htmlOptions']) ? $inp['htmlOptions'] : []])->textarea();
			break;

		case FeedbackModel::TYPE_DROPDOWN:
			echo $form->field($model, $inp['field'], ['inputOptions' => isset($inp['htmlOptions']) ? $inp['htmlOptions'] : []])->dropDownList($inp['values']);
			break;
	}
}

?>
	<div class="form-group">
		<div class="col-sm-6 col-sm-offset-3">
			<?= Html::submitButton($widget->sendButtonLabel, ['class' => 'btn btn-primary']); ?>
		</div>
		<div class="clearfix"></div>
	</div>

<?php

ActiveForm::end();