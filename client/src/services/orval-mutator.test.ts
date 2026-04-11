import axiosInstance from '@/services/axios';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { customInstance } from './orval-mutator';

vi.mock('@/services/axios', () => ({
	default: vi.fn(),
}));

describe('customInstance', () => {
	beforeEach(() => {
		vi.mocked(axiosInstance).mockReset();
	});

	it('returns response data and merges request options', async () => {
		vi.mocked(axiosInstance).mockResolvedValue({
			data: { ok: true },
		} as never);

		const result = await customInstance<{ ok: boolean }>(
			{
				url: '/articles',
				method: 'get',
				headers: {
					Accept: 'application/json',
				},
			},
			{
				params: {
					page: 2,
				},
				headers: {
					'X-Test': '1',
				},
			},
		);

		expect(axiosInstance).toHaveBeenCalledWith({
			baseURL: 'http://localhost:8080/api/v1/',
			url: '/articles',
			method: 'get',
			params: {
				page: 2,
			},
			headers: {
				Accept: 'application/json',
				'X-Test': '1',
			},
		});
		expect(result).toEqual({ ok: true });
	});
});
