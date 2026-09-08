import React, { useState } from 'react';
import { Modal } from 'react-bootstrap';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
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
	const [showDeleteModal, setShowDeleteModal] = useState<number | string | null>(null);

	const handleDeleteModalClose = () => {
		setShowDeleteModal(null);
	};

	const handleDeleteConfirm = (id: number | string) => {
		handleDeleteModalClose();
		onRemoveItem(id);
	};

	const openModal = (modalId: number | string) => {
		setShowDeleteModal(modalId);
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

						<Modal
							show={showDeleteModal === sentence.id}
							onHide={handleDeleteModalClose}
							title="Are You Sure?"
							footer={
								<>
									<Button variant="secondary" onClick={handleDeleteModalClose}>
										Cancel
									</Button>
									<Button variant="danger" onClick={() => handleDeleteConfirm(sentence.id)}>
										Yes, delete
									</Button>
								</>
							}
						/>
					</div>
				);
			})}

			{items.length === 0 && (
				<div className={sharedStyles.emptyState}>
					<p>No saved sentences found.</p>
				</div>
			)}
		</div>
	);
};

export default CatalogueSentenceItems;
