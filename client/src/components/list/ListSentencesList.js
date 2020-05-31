import React, { Component } from 'react'

class ListKanjisList extends Component {
    constructor(props){
        super(props);
        
    }

    render() {
        let { objects } = this.props;
        
        const objectList = objects.map(object => {
          return (
            <tr key={object.id}>
              <th scope="row">{object.id}</th>
              <td>{object.content}</td>
              <td>{object.tatoeba_entry ? 
              ( <a href={`https://tatoeba.org/eng/sentences/show/${object.tatoeba_entry}`} target="_blank">
                {object.tatoeba_entry} {" "}
                <i class="fas fa-external-link-alt"></i>
                </a> ) : "Local"}
              </td>
            </tr>
          )
        })

        return (
          <table className="table table-responsive-md table-bordered table-hover">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Sentence</th>
                <th scope="col">Tatoeba</th>
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
