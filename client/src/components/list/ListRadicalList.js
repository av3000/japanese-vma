import React, { Component } from 'react'

class ListRadicalList extends Component {
    constructor(props){
        super(props);
        
    }

    render() {
        let { objects } = this.props;
        
        const objectList = objects.map(object => {
            return (
                <tr key={object.id}>
                    <th scope="row">{object.id}</th>
                    <td>{object.radical}</td>
                    <td>{object.strokes}</td>
                    <td>{object.meaning}</td>
                    <td>{object.hiragana}</td>
                </tr>
            )
        })
    
        return (
        <table className="table table-responsive-md table-bordered table-hover">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Kanji</th>
                <th scope="col">Strokes</th>
                <th scope="col">Meaning</th>
                <th scope="col">Hiragana</th>
            </tr>
            </thead>
            <tbody>
                {objectList}
            </tbody>
        </table>
        )
    }
}

export default ListRadicalList
