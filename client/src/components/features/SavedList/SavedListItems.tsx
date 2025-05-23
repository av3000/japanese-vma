// @ts-nocheck
/* eslint-disable */
import React from 'react';
import { ObjectTemplates } from '@/shared/constants';
import ListArticlesList from './SavedLists/SavedArticlesList';
import ListKanjisList from './SavedLists/SavedKanjisList';
import ListRadicalList from './SavedLists/SavedRadicalList';
import ListSentencesList from './SavedLists/SavedSentencesList';
import ListWordsList from './SavedLists/SavedWordsList';

const SavedListItems: React.FC = ({ objects, listType, removeFromList, currentUser, listUserId, editToggle }) => {
	const renderListComponent = () => {
		switch (listType) {
			case ObjectTemplates.KNOWNRADICALS:
			case ObjectTemplates.RADICALS:
				return (
					<ListRadicalList
						editToggle={editToggle}
						listUserId={listUserId}
						currentUser={currentUser}
						objects={objects}
						removeFromList={removeFromList}
						heading="Radicals"
					/>
				);
			case ObjectTemplates.KNOWNKANJIS:
			case ObjectTemplates.KANJIS:
				return (
					<ListKanjisList
						editToggle={editToggle}
						listUserId={listUserId}
						currentUser={currentUser}
						objects={objects}
						removeFromList={removeFromList}
						heading="Kanjis"
					/>
				);
			case ObjectTemplates.KNOWNWORDS:
			case ObjectTemplates.WORDS:
				return (
					<ListWordsList
						editToggle={editToggle}
						listUserId={listUserId}
						currentUser={currentUser}
						objects={objects}
						removeFromList={removeFromList}
						heading="Words"
					/>
				);
			case ObjectTemplates.KNOWNSENTENCES:
			case ObjectTemplates.SENTENCES:
				return (
					<ListSentencesList
						editToggle={editToggle}
						listUserId={listUserId}
						currentUser={currentUser}
						objects={objects}
						removeFromList={removeFromList}
						heading="Sentences"
					/>
				);
			case ObjectTemplates.ARTICLES:
				return (
					<ListArticlesList
						editToggle={editToggle}
						listUserId={listUserId}
						currentUser={currentUser}
						objects={objects}
						removeFromList={removeFromList}
						heading="Articles"
					/>
				);
			default:
				return <p>Unknown list type</p>;
		}
	};

	return <div>{renderListComponent()}</div>;
};

export default SavedListItems;
