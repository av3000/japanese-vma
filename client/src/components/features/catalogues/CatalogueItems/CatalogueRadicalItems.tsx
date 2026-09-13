import React, { useRef, useState } from 'react';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { ConfirmModal } from '@/components/shared/modals';
import { useModal } from '@/hooks/useModal';
import { User } from '@/types';
import sharedStyles from './CatalogueItems.module.scss';

interface Radical {
	id: string | number;
	radical: string;
	strokes: number;
	meaning: string;
	hiragana: string;
}

interface CatalogueRadicalItemsProps {
	items: Radical[];
	onRemoveItem: (id: string | number) => void;
	currentUser: User;
	ownerId: string | number;
	editMode?: boolean;
}

const CatalogueRadicalItems: React.FC<CatalogueRadicalItemsProps> = ({
	items,
	onRemoveItem,
	currentUser,
	ownerId,
	editMode = false,
}) => {
	const [pendingRemovalId, setPendingRemovalId] = useState<number | string | null>(null);
	const dialogRef = useRef<HTMLDialogElement>(null);
	const confirmRemoval = useModal(dialogRef, { onClose: () => setPendingRemovalId(null) });

	const openModal = (id: number | string) => {
		setPendingRemovalId(id);
		confirmRemoval.open();
	};

	const handleDeleteConfirm = () => {
		if (pendingRemovalId !== null) {
			onRemoveItem(pendingRemovalId);
		}
		confirmRemoval.close();
	};

	return (
		<div className={classNames(sharedStyles.listContainer, sharedStyles.radicalsContainer)}>
			{items.map((radical) => {
				return (
					<div key={radical.id} className={sharedStyles.itemCard}>
						<div className={sharedStyles.itemHeader}>
							<div className={sharedStyles.characterDisplay}>
								<Link to={`/radical/${radical.id}`}>{radical.radical}</Link>
							</div>

							{currentUser.id === ownerId && editMode && (
								<Button
									type="button"
									size="md"
									variant="danger"
									aria-label="Remove radical from catalogue"
									onClick={() => openModal(radical.id)}
									className={classNames(sharedStyles.removeButton, sharedStyles.absolute)}
								>
									<Icon size="sm" name="minusSolid" />
								</Button>
							)}
						</div>

						<div className={sharedStyles.itemDetails}>
							<div className={sharedStyles.detailItem}>
								<span className={sharedStyles.detailLabel}>Meaning:</span>
								<span className={sharedStyles.detailValue}>{radical.meaning}</span>
							</div>

							<div className={sharedStyles.detailItem}>
								<span className={sharedStyles.detailLabel}>Hiragana:</span>
								<span className={sharedStyles.detailValue}>{radical.hiragana || '—'}</span>
							</div>
						</div>

						<div className={classNames(sharedStyles.metaInfo, sharedStyles.centered)}>
							<div className={sharedStyles.badge}>
								<span>
									{radical.strokes} {radical.strokes === 1 ? 'stroke' : 'strokes'}
								</span>
							</div>
						</div>
					</div>
				);
			})}

			<ConfirmModal
				controller={confirmRemoval}
				title="Are you sure?"
				confirmLabel="Yes, delete"
				confirmVariant="danger"
				ariaLabel="Remove radical from catalogue"
				onConfirm={handleDeleteConfirm}
			>
				This removes the radical from the catalogue. You can add it again later.
			</ConfirmModal>

			{items.length === 0 && (
				<div className={sharedStyles.emptyState}>
					<p>No saved radicals found.</p>
				</div>
			)}
		</div>
	);
};

export default CatalogueRadicalItems;
