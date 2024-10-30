import React, { Component } from "react";
import ListRadicalList from "./ListRadicalList";
import ListKanjisList from "./ListKanjisList";
import ListWordsList from "./ListWordsList";
import ListSentencesList from "./ListSentencesList";
import ListArticlesList from "./ListArticlesList";
import { ObjectTemplates } from "../../shared/constants";

class ListItems extends Component {
  render() {
    const {
      objects,
      listType,
      removeFromList,
      currentUser,
      listUserId,
      editToggle,
    } = this.props;

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
        return "Unknown list type";
    }
  }
}

export default ListItems;
