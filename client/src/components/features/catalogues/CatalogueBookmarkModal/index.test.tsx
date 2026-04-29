import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import { CatalogueBookmarkModal } from './index';

describe('CatalogueBookmarkModal', () => {
	it('renders canonical catalogue detail and create links only', () => {
		const html = renderToStaticMarkup(
			<MemoryRouter>
				<CatalogueBookmarkModal
					controller={{
						id: 'catalogue-bookmark-modal',
						dialogRef: { current: null },
						isOpen: true,
						isRendered: true,
						open: () => {},
						close: () => {},
					}}
					lists={[
						{
							id: 9,
							uuid: 'd453be67-1519-43e2-94ab-af85b79aeb31',
							title: 'My catalogue',
							type: 5,
							elementBelongsToList: false,
						},
					]}
					loadingListIds={[]}
					onListAction={() => {}}
				/>
			</MemoryRouter>,
		);

		expect(html).toContain(`href="${CATALOGUE_ROUTES.detail('d453be67-1519-43e2-94ab-af85b79aeb31')}"`);
		expect(html).toContain(`href="${CATALOGUE_ROUTES.create}"`);
		expect(html).not.toContain('/list/9');
		expect(html).not.toContain('/newlist');
	});
});
