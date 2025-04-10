// @ts-nocheck

import React from "react";
import ListRadicalList from "./SavedLists/ListRadicalList";
import ListKanjisList from "./SavedLists/ListKanjisList";
import ListWordsList from "./SavedLists/SavedWordsList";
import ListSentencesList from "./SavedLists/SavedSentencesList";
import ListArticlesList from "./SavedLists/SavedArticlesList";
import { ObjectTemplates } from "@/shared/constants";

const SavedListItems: React.FC = ({
  objects,
  listType,
  removeFromList,
  currentUser,
  listUserId,
  editToggle,
}) => {
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
