import { describe, expect, it } from 'vitest';
import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import {
	approvalHint,
	formatCount,
	formatDashboardDate,
	PUBLICITY,
	toCount,
	visibilityDisplay,
	visibleProcessingStatus,
} from './dashboardValues';

describe('visibilityDisplay', () => {
	it('labels public and private with text and an icon', () => {
		expect(visibilityDisplay(PUBLICITY.PUBLIC)).toEqual({ label: 'Public', icon: 'eyeRegular' });
		expect(visibilityDisplay(PUBLICITY.PRIVATE)).toEqual({ label: 'Private', icon: 'lockSolid' });
	});

	it('treats an unknown value as private', () => {
		expect(visibilityDisplay(7).label).toBe('Private');
	});
});

describe('approvalHint', () => {
	it('points a rejected article at Edit, and says nothing otherwise', () => {
		expect(approvalHint(ARTICLE_STATUS.REJECTED)).toBe('Edit and resubmit');

		for (const status of [ARTICLE_STATUS.PENDING, ARTICLE_STATUS.REVIEWING, ARTICLE_STATUS.APPROVED]) {
			expect(approvalHint(status)).toBeNull();
		}
	});
});

describe('visibleProcessingStatus', () => {
	it('keeps the states that need attention', () => {
		for (const status of [ProcessingStatus.pending, ProcessingStatus.processing, ProcessingStatus.failed]) {
			expect(visibleProcessingStatus({ status })).toBe(status);
		}
	});

	it('hides finished runs and articles without a run', () => {
		expect(visibleProcessingStatus({ status: ProcessingStatus.completed })).toBeNull();
		expect(visibleProcessingStatus({ status: ProcessingStatus.superseded })).toBeNull();
		expect(visibleProcessingStatus(null)).toBeNull();
		expect(visibleProcessingStatus(undefined)).toBeNull();
	});
});

describe('toCount', () => {
	it('reads the string counts the API sends', () => {
		expect(toCount('12')).toBe(12);
		expect(toCount(3)).toBe(3);
	});

	it('turns anything unusable into zero', () => {
		expect(toCount(undefined)).toBe(0);
		expect(toCount(null)).toBe(0);
		expect(toCount('n/a')).toBe(0);
		expect(toCount(-4)).toBe(0);
	});

	it('formats large counts with separators', () => {
		expect(formatCount(13108)).toBe('13,108');
	});
});

describe('formatDashboardDate', () => {
	it('formats as day, short month, year', () => {
		expect(formatDashboardDate('2026-10-02T12:00:00Z')).toBe('2 Oct 2026');
	});

	it('returns an unparseable value as it came', () => {
		expect(formatDashboardDate('not a date')).toBe('not a date');
	});
});
