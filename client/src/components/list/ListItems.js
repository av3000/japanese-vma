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
        let { objects, listType } = this.props; 
        switch(listType) {
            case 1:
            case 5:
                return (
                    <ListRadicalList objects={objects} heading="Radicals" />
                )
            case 2:
            case 6:
                return (
                    <ListKanjisList objects={objects} heading="Kanjis" />
                )
            case 3:
            case 7:
                return (
                    <ListWordsList objects={objects} heading="Words" />
                )
            case 4:
            case 8:
                return (
                    <ListSentencesList objects={objects} heading="Sentences" />
                )
            case 9:
                return (
                    <ListArticlesList objects={objects} heading="Articles" />
                )
            default:
                return "Emptyness inside of me"
        }
    }
}

export default ListItems;
