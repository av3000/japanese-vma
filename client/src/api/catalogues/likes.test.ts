import { beforeEach, describe, expect, it, vi } from 'vitest';
import { apiCall } from '@/services/api';
import { ObjectTemplateType, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import { toggleInstanceLike } from '@/api/likes/likes';
import { fetchCatalogueLikeStatus, toggleCatalogueLike } from './likes';

vi.mock('@/services/api', () => ({
	apiCall: vi.fn(),
}));

vi.mock('@/api/likes/likes', () => ({
	toggleInstanceLike: vi.fn(),
}));

describe('catalogue likes', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('reads the current catalogue like status', async () => {
		vi.mocked(apiCall).mockResolvedValue({ isLiked: true });

		await expect(fetchCatalogueLikeStatus(12)).resolves.toBe(true);
		expect(apiCall).toHaveBeenCalledWith({
			method: 'post',
			path: '/list/12/checklike',
		});
	});

	it('toggles catalogue likes through the shared v1 like-instance endpoint', async () => {
		vi.mocked(toggleInstanceLike).mockResolvedValue({ success: true, like: true });

		await expect(toggleCatalogueLike(12)).resolves.toEqual({ success: true, like: true });
		expect(toggleInstanceLike).toHaveBeenCalledWith({
			objectType: 'List',
			objectTypeId: ObjectTemplateTypeLegacyId[ObjectTemplateType.LIST],
			instanceId: 12,
		});
	});
});
