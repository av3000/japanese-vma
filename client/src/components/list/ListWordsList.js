import React, { Component } from 'react'

class ListKanjisList extends Component {
    constructor(props){
        super(props);
        
    }

    render() {
        let { objects } = this.props;
        
        const objectList = objects.map(object => {
          console.log(object);
          // object.onyomi = object.onyomi.split("|");
          // object.onyomi = object.onyomi.slice(0, 3);
          // object.onyomi = object.onyomi.join(", ");

          // object.kunyomi = object.kunyomi.split("|");
          // object.kunyomi = object.kunyomi.slice(0, 3);
          // object.kunyomi = object.kunyomi.join(", ");

          // object.meaning = object.meaning.split("|");
          // object.meaning = object.meaning.slice(0, 3);
          // object.meaning = object.meaning.join(", ");

          // object.kunyomi = object.kunyomi.join(", ", object.kunyomi.slice(object.kunyomi.split("|", object.kunyomi), 0, 3));
          // object.meaning = object.meaning.join(", ", object.meaning.slice(object.meaning.split("|", object.meaning), 0, 3));

          return (
            <tr key={object.id}>
              <th scope="row">{object.id}</th>
              <td>{object.word}</td>
              <td>{object.furigana}</td>
              <td>{object.meaning}</td>
              <td>{object.jlpt}</td>
              <td>{object.word_type}</td>
            </tr>
          )
        })

        return (
          <table className="table table-responsive-md table-bordered table-hover">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Word</th>
                <th scope="col">Furigana</th>
                <th scope="col">Meaning</th>
                <th scope="col">JLPT</th>
                <th scope="col">Type</th>
              </tr>
            </thead>
            <tbody>
              {objectList}
            </tbody>
          </table>
        )
    }
}

export default ListKanjisList
