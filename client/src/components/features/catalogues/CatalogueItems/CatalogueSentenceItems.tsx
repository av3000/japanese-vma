import React, { useRef, useState } from 'react';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { ConfirmModal } from '@/components/shared/modals';
import { useModal } from '@/hooks/useModal';
import { User } from '@/types';
import sharedStyles from './CatalogueItems.module.scss';

interface Sentence {
	id: string | number;
	content: string;
	tatoeba_entry?: string | number;
}

interface CatalogueSentenceItemsProps {
	items: Sentence[];
	onRemoveItem: (id: string | number) => void;
	currentUser: User;
	ownerId: string | number;
	editMode?: boolean;
}

const CatalogueSentenceItems: React.FC<CatalogueSentenceItemsProps> = ({
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
		<div className={classNames(sharedStyles.listContainer, sharedStyles.sentencesContainer)}>
			{items.map((sentence) => {
				return (
					<div key={sentence.id} className={sharedStyles.itemCard}>
						<div className={sharedStyles.itemHeader}>
							<div className={sharedStyles.detailValue}>{sentence.content}</div>

							{currentUser.id === ownerId && editMode && (
								<Button
									type="button"
									size="md"
									variant="danger"
									aria-label="Remove sentence from catalogue"
									onClick={() => openModal(sentence.id)}
									className={classNames(sharedStyles.removeButton, sharedStyles.absolute)}
								>
									<Icon size="sm" name="minusSolid" />
								</Button>
							)}
						</div>

						<div className={sharedStyles.metaInfo}>
							{sentence.tatoeba_entry ? (
								<a
									href={`https://tatoeba.org/eng/sentences/show/${sentence.tatoeba_entry}`}
									target="_blank"
									rel="noopener noreferrer"
									className={sharedStyles.externalLink}
								>
									<span>Tatoeba #{sentence.tatoeba_entry}</span>
									<Icon size="sm" name="externalLink" />
								</a>
							) : (
								<span className={sharedStyles.badge}>Local</span>
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
				ariaLabel="Remove sentence from catalogue"
				onConfirm={handleDeleteConfirm}
			>
				This removes the sentence from the catalogue. You can add it again later.
			</ConfirmModal>

			{items.length === 0 && (
				<div className={sharedStyles.emptyState}>
					<p>No saved sentences found.</p>
				</div>
			)}
		</div>
	);
};

export default CatalogueSentenceItems;
