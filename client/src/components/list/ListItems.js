import React, { Component } from 'react'
import ListRadicalList from './ListRadicalList';
import ListKanjisList from './ListKanjisList';
import ListWordsList from './ListWordsList';
import ListSentencesList from './ListSentencesList';
import ListArticlesList from './ListArticlesList';

class ListItems extends Component {
    constructor(props){
        super(props);
    }

    render() {
        let { objects, listType, removeFromList, currentUser, listUserId } = this.props; 
        console.log("cyrrentUser");
        console.log(currentUser);
        switch(listType) {
            case 1:
            case 5:
                return (
                    <ListRadicalList listUserId={listUserId} currentUser={currentUser} objects={objects} removeFromList={removeFromList}  heading="Radicals" />
                )
            case 2:
            case 6:
                return (
                    <ListKanjisList listUserId={listUserId} currentUser={currentUser} objects={objects} removeFromList={removeFromList} heading="Kanjis" />
                )
            case 3:
            case 7:
                return (
                    <ListWordsList listUserId={listUserId} currentUser={currentUser} objects={objects} removeFromList={removeFromList} heading="Words" />
                )
            case 4:
            case 8:
                return (
                    <ListSentencesList listUserId={listUserId} currentUser={currentUser} objects={objects} removeFromList={removeFromList} heading="Sentences" />
                )
            case 9:
                return (
                    <ListArticlesList listUserId={listUserId} currentUser={currentUser} objects={objects} removeFromList={removeFromList} heading="Articles" />
                )
            default:
                return "Emptyness inside of me"
        }
    }
}

export default ListItems;
