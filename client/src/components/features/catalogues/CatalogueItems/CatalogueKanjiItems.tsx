import React, { useState } from 'react';
import { Modal } from 'react-bootstrap';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { User } from '@/types';
import sharedStyles from './CatalogueItems.module.scss';

interface Kanji {
	id: string | number;
	kanji: string;
	onyomi: string;
	kunyomi: string;
	meaning: string;
	jlpt: string;
	frequency: string | number;
}

interface CatalogueKanjiItemsProps {
	items: Kanji[];
	onRemoveItem: (id: string | number) => void;
	currentUser: User;
	ownerId: string | number;
	editMode?: boolean;
}

const CatalogueKanjiItems: React.FC<CatalogueKanjiItemsProps> = ({
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
		<div className={sharedStyles.listContainer}>
			{items.map((kanji) => {
				// Process readings and meanings properly
				const onyomiList = kanji.onyomi.split('|').slice(0, 3).join(', ');
				const kunyomiList = kanji.kunyomi.split('|').slice(0, 3).join(', ');
				const meaningsList = kanji.meaning.split('|').slice(0, 3).join(', ');

				return (
					<div key={kanji.id} className={sharedStyles.itemCard}>
						<div className={sharedStyles.itemHeader}>
							<div className={classNames(sharedStyles.characterDisplay, sharedStyles.large)}>
								<Link to={`/kanji/${kanji.id}`}>{kanji.kanji}</Link>
							</div>

							{currentUser.id === ownerId && editMode && (
								<Button
									type="button"
									size="md"
									variant="danger"
									aria-label="Remove kanji from catalogue"
									onClick={() => openModal(kanji.id)}
									className={classNames(sharedStyles.removeButton, sharedStyles.absolute)}
								>
									<Icon size="sm" name="minusSolid" />
								</Button>
							)}
						</div>

						<div className={sharedStyles.itemDetails}>
							<div className={sharedStyles.detailItem}>
								<span className={sharedStyles.detailLabel}>Meaning:</span>
								<span className={sharedStyles.detailValue}>{meaningsList}</span>
							</div>

							<div className={sharedStyles.detailItem}>
								<span className={sharedStyles.detailLabel}>Onyomi:</span>
								<span className={sharedStyles.detailValue}>{onyomiList || '—'}</span>
							</div>

							<div className={sharedStyles.detailItem}>
								<span className={sharedStyles.detailLabel}>Kunyomi:</span>
								<span className={sharedStyles.detailValue}>{kunyomiList || '—'}</span>
							</div>
						</div>

						<div className={sharedStyles.metaInfo}>
							{kanji.jlpt && (
								<div className={sharedStyles.badge}>
									<span>N{kanji.jlpt}</span>
								</div>
							)}

							{kanji.frequency && (
								<div className={classNames(sharedStyles.badge, sharedStyles.primary)}>
									<span>Frequency: {kanji.frequency}</span>
								</div>
							)}
						</div>

						<Modal
							show={showDeleteModal === kanji.id}
							onHide={handleDeleteModalClose}
							title="Are You Sure?"
							footer={
								<>
									<Button variant="secondary" onClick={handleDeleteModalClose}>
										Cancel
									</Button>
									<Button variant="danger" onClick={() => handleDeleteConfirm(kanji.id)}>
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
					<p>No saved kanji found.</p>
				</div>
			)}
		</div>
	);
};

export default CatalogueKanjiItems;
