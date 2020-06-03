import React, { Component } from 'react'

class ListKanjisList extends Component {
    constructor(props){
        super(props);
        
    }

    render() {
        let { objects, removeFromList, currentUser, listUserId  } = this.props;
        
        const objectList = objects.map(object => {

          object.onyomi = object.onyomi.split("|");
          object.onyomi = object.onyomi.slice(0, 3);
          object.onyomi = object.onyomi.join(", ");

          object.kunyomi = object.kunyomi.split("|");
          object.kunyomi = object.kunyomi.slice(0, 3);
          object.kunyomi = object.kunyomi.join(", ");

          object.meaning = object.meaning.split("|");
          object.meaning = object.meaning.slice(0, 3);
          object.meaning = object.meaning.join(", ");

          // object.kunyomi = object.kunyomi.join(", ", object.kunyomi.slice(object.kunyomi.split("|", object.kunyomi), 0, 3));
          // object.meaning = object.meaning.join(", ", object.meaning.slice(object.meaning.split("|", object.meaning), 0, 3));

          return (
            <tr key={object.id}>
              <th scope="row">{object.id}
              {currentUser.user.id === listUserId ? (<button className="btn btn-sm btn-danger" onClick={removeFromList.bind(this, object.id)}>-</button>) : ""}    
              </th>
              <td>{object.kanji}</td>
              <td>{object.onyomi}</td>
              <td>{object.kunyomi}</td>
              <td>{object.meaning}</td>
              <td>{object.jlpt}</td>
              <td>{object.frequency}</td>
            </tr>
          )
        })

        return (
          <table className="table table-responsive-md table-bordered table-hover">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Kanji</th>
                <th scope="col">Onyomi</th>
                <th scope="col">Kunyomi</th>
                <th scope="col">Meaning</th>
                <th scope="col">JLPT</th>
                <th scope="col">Frequency</th>
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
