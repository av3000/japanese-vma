import React, { Component } from "react";
import ListRadicalList from "./ListRadicalList";
import ListKanjisList from "./ListKanjisList";
import ListWordsList from "./ListWordsList";
import ListSentencesList from "./ListSentencesList";
import ListArticlesList from "./ListArticlesList";

class ListItems extends Component {
  render() {
    let {
      objects,
      listType,
      removeFromList,
      currentUser,
      listUserId,
      editToggle,
    } = this.props;

    switch (listType) {
      case 1:
      case 5:
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
      case 2:
      case 6:
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
      case 3:
      case 7:
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
      case 4:
      case 8:
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
      case 9:
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
        return "Emptyness inside of me";
    }
  }
}

export default ListItems;
