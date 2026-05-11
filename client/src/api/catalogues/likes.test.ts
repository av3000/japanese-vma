import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ObjectTemplateType, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import { toggleInstanceLike } from '@/api/likes/likes';
import { toggleCatalogueLike } from './likes';

vi.mock('@/api/likes/likes', () => ({
	toggleInstanceLike: vi.fn(),
}));

describe('catalogue likes', () => {
	beforeEach(() => {
		vi.clearAllMocks();
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
