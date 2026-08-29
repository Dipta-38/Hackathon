import './bootstrap';
import { createWorker } from 'tesseract.js';

const normalizeDigits = (value) => value.replace(/[০-৯]/g, (digit) => '০১২৩৪৫৬৭৮৯'.indexOf(digit));

const findNidNumber = (text) => {
	const normalizedText = normalizeDigits(text);
	const groupedCandidates = normalizedText.match(/\b(?:\d[\s-]*){10,17}\b/g) || [];
	const candidates = groupedCandidates
		.map((candidate) => candidate.replace(/[\s-]/g, ''))
		.filter((candidate) => /^\d{10,17}$/.test(candidate));

	return candidates.sort((first, second) => {
		const preferredLength = (length) => [10, 13, 17].includes(length) ? 0 : 1;
		return preferredLength(first.length) - preferredLength(second.length);
	})[0] || '';
};

document.querySelectorAll('.nid-scan-input').forEach((input) => {
	input.addEventListener('change', async () => {
		const file = input.files?.[0];
		const status = input.parentElement.querySelector('.nid-scan-status');
		const target = document.getElementById(input.dataset.target);
		if (!file || !target) return;

		input.disabled = true;
		status.textContent = 'Reading NID image locally...';
		try {
			const worker = await createWorker('eng');
			const result = await worker.recognize(file);
			const nidNumber = findNidNumber(result.data.text);
			await worker.terminate();

			if (!nidNumber) {
				throw new Error('No NID number found');
			}

			target.value = nidNumber;
			status.textContent = 'NID number extracted. Review it before continuing.';
			status.classList.add('text-success');
		} catch (error) {
			status.textContent = 'Could not read the number. Use a clearer image and try again.';
			status.classList.add('text-danger');
		} finally {
			input.disabled = false;
		}
	});
});
