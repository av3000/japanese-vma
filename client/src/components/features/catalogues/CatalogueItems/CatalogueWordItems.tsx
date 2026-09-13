import React, { useRef, useState } from 'react';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { ConfirmModal } from '@/components/shared/modals';
import { useModal } from '@/hooks/useModal';
import { User } from '@/types';
import sharedStyles from './CatalogueItems.module.css';

interface Word {
	id: string | number;
	word: string;
	furigana: string;
	meaning: string;
	jlpt: string;
	word_type: string;
}

interface CatalogueWordItemsProps {
	items: Word[];
	onRemoveItem: (id: string | number) => void;
	currentUser: User;
	ownerId: string | number;
	editMode?: boolean;
}

const CatalogueWordItems: React.FC<CatalogueWordItemsProps> = ({
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
		<div className={sharedStyles.listContainer}>
			{items.map((word) => {
				// Process meaning properly
				const meanings = word.meaning.split(',').slice(0, 3).join(', ');

				return (
					<div key={word.id} className={sharedStyles.itemCard}>
						<div className={sharedStyles.itemHeader}>
							<div className={sharedStyles.itemDetails}>
								<h3 className={sharedStyles.articleTitle}>
									<Link to={`/word/${word.id}`}>
										{word.word}
										<Icon size="sm" name="externalLink" />
									</Link>
								</h3>
								<div className={sharedStyles.detailValue}>{word.furigana}</div>
							</div>

							{currentUser.id === ownerId && editMode && (
								<Button
									type="button"
									size="md"
									variant="danger"
									aria-label="Remove word from catalogue"
									onClick={() => openModal(word.id)}
									className={sharedStyles.removeButton}
								>
									<Icon size="sm" name="minusSolid" />
								</Button>
							)}
						</div>

						<div className={sharedStyles.detailItem}>
							<span className={sharedStyles.detailLabel}>Meaning:</span>
							<span className={sharedStyles.detailValue}>{meanings}</span>
						</div>

						<div className={sharedStyles.metaInfo}>
							{word.jlpt && (
								<div className={sharedStyles.badge}>
									<span>{word.jlpt}</span>
								</div>
							)}

							{word.word_type && (
								<div className={sharedStyles.badge}>
									<span>{word.word_type}</span>
								</div>
							)}
						</div>
					</div>
				);
			})}

			<ConfirmModal
				controller={confirmRemoval}
				title="Are you sure?"
				confirmLabel="Yes, delete"
				confirmVariant="danger"
				ariaLabel="Remove word from catalogue"
				onConfirm={handleDeleteConfirm}
			>
				This removes the word from the catalogue. You can add it again later.
			</ConfirmModal>

			{items.length === 0 && (
				<div className={sharedStyles.emptyState}>
					<p>No saved words found.</p>
				</div>
			)}
		</div>
	);
};

export default CatalogueWordItems;
