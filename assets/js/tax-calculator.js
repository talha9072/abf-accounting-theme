/**
 * ABF Accounting — take-home pay calculator (Resources page).
 *
 * Estimate for an employee on a standard tax code (1257L). Pension is treated as a net-pay
 * contribution (taken before Income Tax, not before National Insurance). Personal Allowance
 * tapers by £1 for every £2 of adjusted income over £100,000.
 *
 * Add a new tax year by adding an entry to RATES and an <option> to the Tax year select.
 * Sources: GOV.UK "Income Tax rates and Personal Allowances", gov.scot "Scottish Income Tax
 * rates and bands", GOV.UK "Rates and thresholds for employers", Student Loans Company.
 */
(function () {
	'use strict';

	// Bands are cumulative limits of taxable income (income above the Personal Allowance).
	// The higher-rate (rUK) and advanced-rate (Scotland) bands always end at £125,140.
	var RATES = {
		2026: {
			allowance: 12570,
			taperFrom: 100000,
			ruk: [[37700, 0.2], [125140, 0.4], [Infinity, 0.45]],
			scot: [[3967, 0.19], [16956, 0.2], [31092, 0.21], [62430, 0.42], [125140, 0.45], [Infinity, 0.48]],
			ni: { from: 12570, upper: 50270, main: 0.08, above: 0.02 },
			loans: { plan1: [26900, 0.09], plan2: [29385, 0.09], plan4: [33795, 0.09], plan5: [25000, 0.09], pg: [21000, 0.06] }
		},
		2025: {
			allowance: 12570,
			taperFrom: 100000,
			ruk: [[37700, 0.2], [125140, 0.4], [Infinity, 0.45]],
			scot: [[2827, 0.19], [14921, 0.2], [31092, 0.21], [62430, 0.42], [125140, 0.45], [Infinity, 0.48]],
			ni: { from: 12570, upper: 50270, main: 0.08, above: 0.02 },
			// Plan 5 repayments start in April 2026.
			loans: { plan1: [26065, 0.09], plan2: [28470, 0.09], plan4: [32745, 0.09], plan5: [Infinity, 0.09], pg: [21000, 0.06] }
		}
	};

	function parse(value) {
		var n = parseFloat(String(value).replace(/[£,%\s]/g, ''));
		return isFinite(n) && n > 0 ? n : 0;
	}

	function money(n) {
		return '£' + Math.round(n).toLocaleString('en-GB');
	}

	function bandTax(taxable, bands) {
		var tax = 0;
		var floor = 0;
		for (var i = 0; i < bands.length && taxable > floor; i++) {
			var top = bands[i][0];
			tax += (Math.min(taxable, top) - floor) * bands[i][1];
			floor = top;
		}
		return tax;
	}

	function calculate(input) {
		var r = RATES[input.year] || RATES[2026];
		var gross = input.salary;
		var pension = gross * Math.min(input.pension, 100) / 100;
		var income = Math.max(0, gross - pension);

		var allowance = r.allowance;
		if (income > r.taperFrom) {
			allowance = Math.max(0, allowance - (income - r.taperFrom) / 2);
		}
		var tax = bandTax(Math.max(0, income - allowance), input.region === 'scot' ? r.scot : r.ruk);

		var ni = 0;
		if (gross > r.ni.from) {
			ni = (Math.min(gross, r.ni.upper) - r.ni.from) * r.ni.main;
			if (gross > r.ni.upper) {
				ni += (gross - r.ni.upper) * r.ni.above;
			}
		}

		var loan = 0;
		var plan = r.loans[input.loan];
		if (plan && gross > plan[0]) {
			loan = Math.floor((gross - plan[0]) * plan[1]);
		}

		var total = tax + ni + pension + loan;
		var net = gross - total;

		return {
			gross: gross,
			tax: tax,
			ni: ni,
			pension: pension,
			loan: loan,
			total: total,
			net: net,
			rate: gross ? (tax + ni) / gross * 100 : 0
		};
	}

	function init(root) {
		var form = root.querySelector('form');
		var salary = form.querySelector('[name="salary"]');
		var error = form.querySelector('.abf-calc__error');
		var outs = {};
		root.querySelectorAll('[data-out]').forEach(function (el) {
			outs[el.getAttribute('data-out')] = el;
		});

		function run(showError) {
			var value = parse(salary.value);
			if (!value) {
				if (showError) {
					error.hidden = false;
					salary.setAttribute('aria-invalid', 'true');
					salary.focus();
				}
				return;
			}
			error.hidden = true;
			salary.removeAttribute('aria-invalid');

			var res = calculate({
				salary: value,
				year: form.querySelector('[name="year"]').value,
				region: form.querySelector('[name="region"]').value,
				pension: parse(form.querySelector('[name="pension"]').value),
				loan: form.querySelector('[name="loan"]').value
			});

			outs.net.textContent = money(res.net);
			outs.month.textContent = money(res.net / 12);
			outs.week.textContent = money(res.net / 52);
			['gross', 'tax', 'ni', 'pension', 'loan', 'total'].forEach(function (key) {
				outs[key].textContent = money(res[key]);
			});
			outs.rate.textContent = res.rate.toFixed(1) + '%';
			root.classList.add('is-calculated');
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			run(true);
		});

		// Recalculate when an option changes after the first result.
		form.addEventListener('change', function () {
			if (root.classList.contains('is-calculated')) {
				run(false);
			}
		});

		// Format the salary with thousands separators when leaving the field.
		salary.addEventListener('blur', function () {
			var value = parse(salary.value);
			if (value) {
				salary.value = value.toLocaleString('en-GB', { maximumFractionDigits: 2 });
			}
		});
	}

	document.querySelectorAll('[data-abf-taxcalc]').forEach(init);

	// Exposed for testing.
	window.abfTaxCalc = calculate;
})();
