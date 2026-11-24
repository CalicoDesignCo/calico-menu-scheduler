(function () {
	'use strict';

	const form = document.getElementById('update-nav-menu');
	const periodTemplate = document.getElementById('calico-menu-period-template');
	const ruleTemplate = document.getElementById('calico-menu-rule-template');
	if (!form || !periodTemplate || !ruleTemplate) {
		return;
	}

	const initialValues = new WeakMap();
	const defaultRule = { type: 'from', date: '' };
	const endRule = { type: 'through', date: '' };

	function addRule(period, rule) {
		const row = ruleTemplate.content.firstElementChild.cloneNode(true);
		row.querySelector('select').value = rule.type;
		row.querySelector('input').value = rule.date;
		period.querySelector('[data-calico-rules]').append(row);
		return row;
	}

	function addPeriod(panel, rules) {
		const period = periodTemplate.content.firstElementChild.cloneNode(true);
		rules.forEach(rule => addRule(period, rule));
		panel.querySelector('[data-calico-periods]').append(period);
		return period;
	}

	function render(panel) {
		const value = panel.querySelector('input[type="hidden"]').value;
		panel.querySelector('[data-calico-periods]').replaceChildren();
		JSON.parse(value).forEach(rules => addPeriod(panel, rules));
		panel.querySelector('.calico-menu-error').hidden = true;
		panel.hidden = false;
	}

	function sync(panel) {
		const periods = Array.from(panel.querySelectorAll('.calico-menu-period'), period =>
			Array.from(period.querySelectorAll('.calico-menu-rule'), row => ({
				type: row.querySelector('select').value,
				date: row.querySelector('input').value
			}))
		);
		const input = panel.querySelector('input[type="hidden"]');
		input.value = JSON.stringify(periods);
		input.dispatchEvent(new Event('change', { bubbles: true }));
	}

	form.querySelectorAll('.calico-menu-schedule').forEach(render);

	form.addEventListener('click', event => {
		const toggle = event.target.closest('.item-edit, .item-cancel');
		if (toggle) {
			const item = toggle.closest('.menu-item');
			const panel = item.querySelector('.calico-menu-schedule');
			if (!panel) {
				return;
			}
			const input = panel.querySelector('input[type="hidden"]');
			if (toggle.matches('.item-cancel') && initialValues.has(panel)) {
				input.value = initialValues.get(panel);
				render(panel);
			} else if (item.classList.contains('menu-item-edit-inactive')) {
				if (panel.hidden) {
					render(panel);
				}
				initialValues.set(panel, input.value);
			}
			return;
		}

		const button = event.target.closest('[data-calico-action]');
		if (!button) {
			return;
		}

		const panel = button.closest('.calico-menu-schedule');
		const period = button.closest('.calico-menu-period');
		let focus;
		switch (button.dataset.calicoAction) {
			case 'add-period':
				focus = addPeriod(panel, [defaultRule]).querySelector('input');
				break;
			case 'add-rule':
				focus = addRule(period, endRule).querySelector('input');
				break;
			case 'remove-rule': {
				const rule = button.closest('.calico-menu-rule');
				if (rule.parentElement.children.length > 1) {
					focus = period.querySelector('[data-calico-action="add-rule"]');
					rule.remove();
					break;
				}
				// Removing the only rule also removes its empty period.
			}
			// Falls through.
			case 'remove-period':
				focus = panel.querySelector('[data-calico-action="add-period"]');
				period.remove();
				break;
		}
		panel.querySelector('.calico-menu-error').hidden = true;
		sync(panel);
		focus.focus();
	}, true);

	form.addEventListener('change', event => {
		if (!event.target.matches('.calico-menu-rule input, .calico-menu-rule select')) {
			return;
		}
		const panel = event.target.closest('.calico-menu-schedule');
		event.target.removeAttribute('aria-invalid');
		panel.querySelector('.calico-menu-error').hidden = true;
		sync(panel);
	});

	function validate(event) {
		const dates = form.querySelectorAll('.calico-menu-rule input');
		for (const date of dates) {
			if (/^\d{4}-\d{2}-\d{2}$/.test(date.value) && !date.validity.badInput) {
				continue;
			}
			event.preventDefault();
			event.stopImmediatePropagation();
			const item = date.closest('.menu-item');
			if (item.classList.contains('menu-item-edit-inactive')) {
				item.querySelector('.item-edit').click();
			}
			date.setAttribute('aria-invalid', 'true');
			date.closest('.calico-menu-schedule').querySelector('.calico-menu-error').hidden = false;
			date.focus();
			return false;
		}
		return true;
	}

	form.addEventListener('click', event => {
		if (event.target.closest('.menu-save')) {
			validate(event);
		}
	}, true);

	// Run before WordPress serializes the menu form, including saves via the keyboard.
	form.addEventListener('submit', event => {
		if (validate(event)) {
			form.querySelectorAll('.calico-menu-schedule:not([hidden])').forEach(sync);
		}
	}, true);
}());
