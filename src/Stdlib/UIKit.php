<?php

namespace dsLive\Stdlib;

use dScribe\Form\Element,
	dScribe\Form\Form,
	Object;

/**
 * Description of UIKit
 *
 * @author Ezra
 */
class UIKit {

	public static function accordion(array $content, $openMultiple = false) {
		?>
		<div class="uk-accordion" data-uk-accordion="<?= $openMultiple ? '{collapse:false}' : '' ?>">
			<?php foreach ($content as $title => $cont): ?>
				<h3 class="uk-accordion-title"><?= $title ?></h3>
				<div class="uk-accordion-content"><?= $cont ?></div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	public static function dropdowns() {
		
	}

	public static function dynamicGrid() {
		
	}

	public static function grid() {
		
	}

	public static function icons() {
		
	}

	private static $modals = 1;
	private static $loadModalScript = true;

	/**
	 * 
	 * @param array $options Keys include:<br />
	 * - id (string): Each modal must have a unique id. This will be needed to create the modal link
	 * - modalClass (string): The class to add to the modal container
	 * - linkText (string): Custom link text if need link to show. Default is "Open"
	 * - large (boolean): Indicates whether the modal should be large or not. Default is FALSE
	 * - header (string): The modal header
	 * - headerTag (string): Default is h3
	 * - headerClass (string): The class to add to the header container
	 * - closeBtn (boolean): Indicates whether to show close button. Default is TRUE
	 * - body (string): The body of the modal.
	 * - footer (string): The modal footer
	 * - footerClass (string): The class to add the footer container
	 * 
	 * @param boolean $showLink Indicates whether to show the link that'll open the modal
	 */
	public static function modal(array $options, $showLink = true) {
		ob_start();
		if (!@$options['id']) {
			$options['id'] = 'modal_' . self::$modals;
			self::$modals++;
		}
		if ($showLink)
				static::modalLink($options['id'], @$options['linkText'] ? $options['linkText'] : 'Open');
		if (!@$options['body'] && @$options['href'])
				$options['body'] = '<div class="uk-text-center"><i class="uk-icon-spinner uk-icon-spin uk-text-large"></i></div>';
		?>
		<div class="uk-modal <?= @$options['href'] ? 'load' : '' ?> <?= @$options['cacheData'] ? 'cache-data' : '' ?>" data-href="<?= @$options['href'] ?>" id="<?= $options['id'] ?>">
			<div class="uk-modal-dialog <?= @$options['large'] ? 'uk-modal-dialog-large ' . @$options['modalClass'] : @$options['modalClass'] ?>">
				<?php if (@$options['closeBtn'] !== FALSE): ?>
					<button type="button" class="uk-modal-close uk-close"></button>		
				<?php endif ?>
				<?php
				if (@$options['header']):
					if (!@$options['headerTag']) $options['headerTag'] = 'h3';
					?>
					<div class="uk-modal-header <?= @$options['headerClass'] ?>">
						<<?= $options['headerTag'] ?> class="uk-modal-title"><?= $options['header'] ?></<?= $options['headerTag'] ?>>
					</div>
				<?php endif; ?>
				<div class="uk-modal-body"><?= @$options['body'] ?></div>
				<?php if (@$options['footer']): ?>
					<div class="uk-modal-footer <?= @$options['footerClass'] ?>">
						<?= $options['footer'] ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php if (self::$loadModalScript): self::$loadModalScript = false; ?>
			<script>
				$('body').on('show.uk.modal', '.uk-modal.load', function () {
					$(this).removeClass('load');
					if ($(this).hasClass('cache-data') && localStorage.getItem($(this).data('href'))) {
						if ($(this).find('.uk-modal-dialog').hasClass('no-card')) {
							$(this).find('.uk-modal-body').html(localStorage.getItem($(this).data('href')));
						} else {
							$(this).find('.md-card-content').html(localStorage.getItem($(this).data('href')));
						}
						$(window).resize();
						return;
					}
					$modal = $(this);
					$.get($(this).data('href'), function (data) {
						if ($modal.find('.uk-modal-dialog').hasClass('no-card')) {
							$modal.find('.uk-modal-body').html(data);
						} else {
							$modal.find('.md-card-content').html(data);
						}
						if ($modal.hasClass('cache-data'))
							localStorage.setItem($modal.data('href'), data);
						$(window).resize();
					});
				});
			</script>
			<?php
		endif;
		return ob_get_clean();
	}

	/**
	 * 
	 * @param string $id The id of the modal to target
	 * @param string $text The text of the link
	 * @param array $options Keys include:
	 * - button (boolean): indicates whether the link should be a button or an A tag
	 * - class (string): class to add to the link
	 * - href (string): the link to add to the link if A tag. Default is #
	 */
	public static function modalLink($id, $text = 'Open', array $options = array()) {
		if (@$options['button']):
			?>
			<button class="md-btn <?= @$options['class'] ?>" data-uk-modal="{target:'#<?= $id ?>'}" ><?= $text ?></button>
			<?php
		else:
			if (!@$options['href']) $options['href'] = '#';
			?>
			<a class="md-btn <?= @$options['class'] ?>" href="<?= $options['href'] ?>" data-uk-modal="{target:'#<?= $id ?>'}" ><?= $text ?></a>
		<?php
		endif;
	}

	public static function nestable() {
		
	}

	public static function notify() {
		
	}

	public static function sortable() {
		
	}

	public static function tables() {
		
	}

	public static function tabs() {
		
	}

	private static $formIsWizard = false;
	private static $loadWizardFormScripts = true;

	public static function loadWizardScripts(\dScribe\View\Renderer $renderer) {
		if (!static::$loadWizardFormScripts) return;
		ob_start();
		echo $renderer->loadJs('bower_components/parsleyjs/dist/parsley.min', true);
		echo $renderer->loadJs('js/custom/wizard_steps.min', true);
		?>
		<script>

			$(function () {
				// wizard
				altair_wizard.advanced_wizard();
			});

			// wizard
			altair_wizard = {
				content_height: function (this_wizard, step) {
					var this_height = $(this_wizard).find('.step-' + step).actual('outerHeight');
					$(this_wizard).children('.content').animate({height: this_height}, 280, bez_easing_swiftOut);
				},
				advanced_wizard: function () {
					var $wizard_container = $('.wizard-container');

					if ($wizard_container.length) {
						$wizard_container.each(function () {
							$(this).steps({
								headerTag: "legend.section-title",
								bodyTag: "fieldset",
								transitionEffect: "slideLeft",
								trigger: 'change',
								onInit: function (event, currentIndex) {
									altair_wizard.content_height(this, currentIndex);
									// initialize checkboxes
									altair_md.checkbox_radio($(".wizard-icheck"));
									// reinitialize uikit margin
									altair_uikit.reinitialize_grid_margin();
									// reinitialize selects
									altair_forms.select_elements(this);
									setTimeout(function () {
										$window.resize();
									}, 100);
								},
								onStepChanged: function (event, currentIndex) {
									altair_wizard.content_height(this, currentIndex);
									setTimeout(function () {
										$window.resize();
									}, 100);
								},
								onStepChanging: function (event, currentIndex, newIndex) {
									var step = $(this).find('.body.current').attr('data-step'),
											$current_step = $(this).find('.body[data-step=\"' + step + '\"]');

									// check input fields for errors
									$current_step.find('[data-parsley-id]').each(function () {
										$(this).parsley().validate();
										if ($(this).hasClass('parsley-error'))
											$(this).addClass('md-input-danger');
									});

									// adjust content height
									$window.resize();
									return $current_step.find('.parsley-error').length ? false : true;
								},
								onFinished: function () {
									var form_serialized = JSON.stringify($(this).parent().serializeObject(), null, 2);
									UIkit.modal.alert('<p>Wizard data:</p><pre>' + form_serialized + '</pre>');
								}
							});
							//					$(this).steps("setStep", 1);

							$this_wizard = $(this);
							// wizard
							$(this).closest('form')
									.parsley()
									.on('form:validated', function () {
										setTimeout(function () {
											altair_md.update_input($(this).find('.md-input'));
											// adjust content height
											$window.resize();
										}.bind(this), 100);
									})
									.on('field:validated', function (parsleyField) {
										var $this = $(parsleyField.$element);
										setTimeout(function () {
											altair_md.update_input($this);
											// adjust content height
											var currentIndex = $this_wizard.find('.body.current').attr('data-step');
											if (currentIndex)
												altair_wizard.content_height($this_wizard, currentIndex);
										}, 100);
									});
						});

						$window.on('debouncedresize', function () {
							var current_step = $wizard_container.find('.body.current').attr('data-step');
							if (current_step)
								altair_wizard.content_height($wizard_container, current_step);
						});

					}
				}
			};
		</script>
		<?php
		self::$loadWizardFormScripts = false;
		return ob_get_clean();
	}

	/**
	 * 
	 * @param Form $form
	 * @param bool $inline Indicates whether the form should be inline or stacked
	 * @param bool $simulateValued Indicates whether to add empty values to those with none
	 * @return string
	 */
	public static function form(Form $form, $inline = false, $simulateValued = false) {
		if (!$form) return '';

		if (self::$formIsWizard = $form->getAttribute('wizard') && static::$loadWizardFormScripts)
				return 'You have to call UIKit::loadWizardScripts() first';

		ob_start();
		?>
		<form class="uk-form-<?= $inline ? 'inline' : 'stacked' ?>" <?= $form->parseAttributes(array('wizard')) ?>>
			<?php if (self::$formIsWizard): ?>
				<div class="wizard-container" data-uk-observe>
					<?php
				endif;
				foreach ($form->getElements() as $element) {
					if ($element->is('fieldset')) {
						self::renderFieldset($element);
					}
					else {
						self::renderFormElement($element);
					}
				}
				if (self::$formIsWizard):
					?>
				</div>
				<?php
			endif;
			?>
		</form>
		<?php
		self::$formIsWizard = false;
		return ob_get_clean();
	}

	private static function renderFieldset(Element\Fieldset $fieldset, $ignoreWitchcraft = false) {
		$label = $fieldset->options->label->text;
		if (!$label) $label = $fieldset->options->label;
		if (self::$formIsWizard && !$ignoreWitchcraft) {
			?>
			<legend class="section-title"><?= $label ?></legend>
			<?php
		}
		?>
		<fieldset <?= $fieldset->parseAttributes() ?>>
			<?php
			if ($label):
				?>
				<?php
				if (self::$formIsWizard && !$ignoreWitchcraft):
					?>
					<?php
					if ($fieldset->options->blockInfo) {
						?>
						<h2 class="heading_a">
							<?= $fieldset->options->blockInfoHeading ?>
							<span class="sub-heading"><?= $fieldset->options->blockInfo ?></span>
						</h2>
						<hr />
						<?php
					}
				endif;
				foreach ($fieldset->options->value->getElements() as $elem) {
					if ($elem->is('fieldset')) self::renderFieldset($elem, true);
					else self::renderFormElement($elem);
				}
				if (!self::$formIsWizard || (self::$formIsWizard && $ignoreWitchcraft)):
					?>
					<legend> <?= $label ?> <?= $fieldset->getMultipleButton() ?></legend>
					<?php
				endif;
				?>
			<?php endif; ?>
		</fieldset>
		<?php
	}

	private static $inlineElement = false;

	private static function renderFormElement(Element $element) {
		if ($simulateValued && !$element->getValue()) $element->options->default = ' ';
		if ($element->isHidden()) {
			echo $element->create();
			return;
		}
		else if ($element->is('checkbox', 'radio')) {
			if (!is_object($element->options->label))
					$element->options->label = new Object(array('text' => $element->options->label, 'attrs' => array()));
			if ($inline) $element->options->label->attrs->class .= ' inline-label';
			$element->attributes->dataMdIcheck = '';
		}
		else if (!$element->is('submit', 'file', 'button', 'reset'))
				$element->attributes->class = $element->attributes->class . ' md-input';
		else if ($element->is('submit'))
				$element->attributes->class = $element->attributes->class . ' md-btn-success';

		if (!is_object($element->options->label))
				$element->options->label = new Object(array('text' => $element->options->label, 'attrs' => array()));
		if (!$element->is('checkbox', 'radio') || ($element->is('checkbox', 'radio') && $element->options->values))
				$element->options->label->attrs->class .= ' uk-form-label';
		if ($element->is('submit', 'button', 'reset')) $element->attributes->class .= ' md-btn';

		$class = 'uk-form-row';
		$parsley = $element->attributes->required ? ' parsley-row' : '';
		if ($element->options->inline) {
			$class = $element->options->inline;
			$parsley = 'parsley-row';
			if (!self::$inlineElement) {
				?>
				<div class="uk-grid">
					<?php
				}
			}
			else if (self::$inlineElement) {
				?>
			</div>
			<?php
			self::$inlineElement = false;
		}
		?>
		<div class="<?= $class . $parsley ?>">
			<?php if (!$element->is('checkbox', 'radio') || ($element->is('checkbox', 'radio') && $element->options->values)) echo $element->renderLabel(); ?>
			<?php
			if ($element->errors) $element->attributes->class .= ' uk-form-danger';
			if ($element->is('checkbox', 'radio') && $element->options->values) {
				$cnt = 0;
				foreach ($element->options->values as $label => $value) {
					?>
					<span class="icheck-inline">
						<label class="inline-label">
							<input name="<?= $element->name . (($element->isCheckbox() && $element->attributes->multiple) ? '[]' : '') ?>" type="<?= $element->type ?>" data-md-icheck <?= !$cnt ? 'required="required"' : '' ?> value="<?= $value ?>" />
							<?= $label ?>
						</label>
					</span>
					<?php
				}
			}
			else echo $element->create();
			echo $element->errors;
			?>
			<?php if ($element->is('checkbox', 'radio') && !$element->options->values) echo $element->renderLabel(); ?>
			<?php if ($element->options->inlineInfo): ?>
				<span class="uk-form-help-inline"><?= $element->options->inlineInfo ?></span>
			<?php endif; ?>
			<?php if ($element->options->blockInfo): ?>
				<span class="uk-form-help-block"><?= $element->options->blockInfo ?></span>
			<?php endif; ?>
		</div>
		<?php
	}

	private static $toggleCount = 0;

	/**
	 * Toggles between to labels
	 * @param string $label1
	 * @param string $label2
	 * @param array $options Keys include:
	 * - toggleClass: The class to add to the toggle group
	 * - defaultLabel: The label that is active by default. $label1 is default
	 * - activeClass: The class to add to the active label
	 * - label1Value: The value for the first label. The label itself is default
	 * - label2Value: The value for the second label. The label itself is default
	 * - label1Id: The id for the first label button. Default is "b1"
	 * - label2Id: The id for the second label button. Default is "b2"
	 * @return string
	 */
	public static function toggle($label1, $label2, array $options = array()) {
		$defaultOptions = array(
			'toggleClass' => '',
			'defaultLabel' => $label1,
			'activeClass' => 'uk-button-primary',
			'label1Value' => $label1,
			'label2Value' => $label2,
			'label1Id' => 'b1',
			'label2Id' => 'b2',
			'fieldName' => ''
		);

		$options = array_merge($defaultOptions, $options);
		if (!$options['id']) $options['id'] = 'uk-toggle-' . ++self::$toggleCount;
		ob_start();
		?>
		<div id="<?= $options['id'] ?>" class="<?= $options['toggleClass'] ?> uk-toggle">
			<div class="uk-button-group">
				<button id="<?= $options['label1Id'] ?>" type="button" value="<?= $options['label1Value'] ?>" class="uk-button <?= $options['defaultLabel'] == $label1 ? $options['activeClass'] : 'uk-text-muted' ?>"><?= $label1 ?></button>
				<button id="<?= $options['label2Id'] ?>" type="button" value="<?= $options['label2Value'] ?>" class="uk-button <?= $options['defaultLabel'] == $label2 ? $options['activeClass'] : 'uk-text-muted' ?>"><?= $label2 ?></button>
			</div>
			<?php if ($options['fieldName']): ?>
				<input type="hidden" name="<?= $options['fieldName'] ?>" value="<?= $options['defaultLabel'] === $label1 ? $label1 : $label2 ?>" />
			<?php endif; ?>
		</div>
		<script>
			$(function () {
				$('#<?= $options['id'] ?> button').on('click', function () {
					$(this).addClass('<?= $options['activeClass'] ?>').removeClass('uk-text-muted')
							.siblings().removeClass('<?= $options['activeClass'] ?>').addClass('uk-text-muted');
				});
			});
		</script>
		<?php
		return ob_get_clean();
	}

}
