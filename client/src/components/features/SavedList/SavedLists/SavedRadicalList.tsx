import React, { useState } from 'react';
import { Modal } from 'react-bootstrap';

import classNames from 'classnames';

import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';

import sharedStyles from '../SharedListStyles.module.scss';

interface Radical {
  id: string | number;
  radical: string;
  strokes: number;
  meaning: string;
  hiragana: string;
}

interface User {
  user: {
    id: string | number;
  };
}

interface SavedRadicalsListProps {
  objects: Radical[];
  removeFromList: (id: string | number) => void;
  currentUser: User;
  listUserId: string | number;
  editToggle?: boolean;
}

const SavedRadicalsList: React.FC<SavedRadicalsListProps> = ({
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
    <div className={classNames(sharedStyles.listContainer, sharedStyles.radicalsContainer)}>
      {objects.map((radical) => {
        return (
          <div key={radical.id} className={sharedStyles.itemCard}>
            <div className={sharedStyles.itemHeader}>
              <div className={sharedStyles.characterDisplay}>
                <Link to={`/radical/${radical.id}`}>{radical.radical}</Link>
              </div>

              {currentUser.user.id === listUserId && editToggle && (
                <Button
                  type="button"
                  size="md"
                  variant="danger"
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

            <Modal
              show={showDeleteModal === radical.id}
              onHide={handleDeleteModalClose}
              title="Are You Sure?"
              footer={
                <>
                  <Button variant="secondary" onClick={handleDeleteModalClose}>
                    Cancel
                  </Button>
                  <Button variant="danger" onClick={() => handleDeleteConfirm(radical.id)}>
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
          <p>No saved radicals found.</p>
        </div>
      )}
    </div>
  );
};

export default SavedRadicalsList;
