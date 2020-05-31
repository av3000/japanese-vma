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
                console.log("1 or 5");
                return (
                    <ListRadicalList objects={objects} heading="Radicals" />
                )
            case 2:
            case 6:
                console.log("2 or 6");
                return (
                    <ListKanjisList objects={objects} heading="Kanjis" />
                )
            case 3:
            case 7:
                console.log("3 or 7");
                return (
                    <ListWordsList objects={objects} heading="Words" />
                )
            case 4:
            case 8:
                console.log("4 or 8");
                return (
                    <ListSentencesList objects={objects} heading="Sentences" />
                )
            case 9:
                console.log("9");
                return (
                    <ListArticlesList objects={objects} heading="Articles" />
                )
            default:
                console.log("default");
                return "Emptyness inside of me"
        }
    }
}

export default ListItems;
