import React, { useState } from 'react';
import { Modal } from 'react-bootstrap';

import classNames from 'classnames';

import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

import sharedStyles from '../SharedListStyles.module.scss';

interface Sentence {
  id: string | number;
  content: string;
  tatoeba_entry?: string | number;
}

interface User {
  user: {
    id: string | number;
  };
}

interface SavedSentencesListProps {
  objects: Sentence[];
  removeFromList: (id: string | number) => void;
  currentUser: User;
  listUserId: string | number;
  editToggle?: boolean;
}

const SavedSentencesList: React.FC<SavedSentencesListProps> = ({
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
    <div className={classNames(sharedStyles.listContainer, sharedStyles.sentencesContainer)}>
      {objects.map((sentence) => {
        return (
          <div key={sentence.id} className={sharedStyles.itemCard}>
            <div className={sharedStyles.itemHeader}>
              <div className={sharedStyles.detailValue}>{sentence.content}</div>

              {currentUser.user.id === listUserId && editToggle && (
                <Button
                  type="button"
                  size="md"
                  variant="danger"
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

      {objects.length === 0 && (
        <div className={sharedStyles.emptyState}>
          <p>No saved sentences found.</p>
        </div>
      )}
    </div>
  );
};

export default SavedSentencesList;
