import React, { useState } from 'react';
import { Modal } from 'react-bootstrap';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { User } from '@/types';
import sharedStyles from '../SharedListStyles.module.scss';

interface Word {
	id: string | number;
	word: string;
	furigana: string;
	meaning: string;
	jlpt: string;
	word_type: string;
}

interface SavedWordsListProps {
	objects: Word[];
	removeFromList: (id: string | number) => void;
	currentUser: User;
	listUserId: string | number;
	editToggle?: boolean;
}

const SavedWordsList: React.FC<SavedWordsListProps> = ({
	objects,
	removeFromList,
	currentUser,
	listUserId,
	editToggle = false,
}) => {
	const [showDeleteModal, setShowDeleteModal] = useState<number | string | null>(null);

	const handleDeleteModalClose = () => {
		setShowDeleteModal(null);
	};

	const handleDeleteConfirm = (id: number | string) => {
		handleDeleteModalClose();
		removeFromList(id);
	};

	const openModal = (modalId: number | string) => {
		setShowDeleteModal(modalId);
	};

	return (
		<div className={sharedStyles.listContainer}>
			{objects.map((word) => {
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

							{currentUser.id === listUserId && editToggle && (
								<Button
									type="button"
									size="md"
									variant="danger"
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

						<Modal
							show={showDeleteModal === word.id}
							onHide={handleDeleteModalClose}
							title="Are You Sure?"
							footer={
								<>
									<Button variant="secondary" onClick={handleDeleteModalClose}>
										Cancel
									</Button>
									<Button variant="danger" onClick={() => handleDeleteConfirm(word.id)}>
										Yes, delete
									</Button>
								</>
							}
						/>
					</div>
				);
			})}

			{objects.length === 0 && (
				<div className={sharedStyles.emptyState}>
					<p>No saved words found.</p>
				</div>
			)}
		</div>
	);
};

export default SavedWordsList;
