import React, { Component } from 'react';
import { Button, Modal } from 'react-bootstrap';

class ListKanjisList extends Component {
    constructor(props){
        super(props);
        this.state = {
          showDeleteModal: 0
        }

        this.handleDeleteModalClose = this.handleDeleteModalClose.bind(this);        
    }

    deleteItem(id) {
      this.handleDeleteModalClose();
      this.props.removeFromList(id);
    }

    handleDeleteModalClose(){
      this.setState({showDeleteModal: 0})
    }

    openModal(modalId){
        this.setState({showDeleteModal: modalId});
    }

    render() {
        let { objects, currentUser, listUserId, editToggle  } = this.props;
        
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

          return (
            <tr key={object.id}>
              <th scope="row">{object.id}
              {currentUser.user.id === listUserId && editToggle  ? (<button className="btn btn-sm btn-danger" onClick={this.openModal.bind(this, object.id)}>-</button>) : ""}    
              </th>
              <td>{object.kanji}</td>
              <td>{object.onyomi}</td>
              <td>{object.kunyomi}</td>
              <td>{object.meaning}</td>
              <td>{object.jlpt}</td>
              <td>{object.frequency}</td>
              <Modal show={this.state.showDeleteModal === object.id} onHide={this.handleDeleteModalClose}>
                <Modal.Header closeButton>
                    <Modal.Title>Are You Sure? </Modal.Title>
                </Modal.Header>
                <Modal.Footer>
                    <div className="col-12">
                    <Button variant="secondary" className="float-left" onClick={this.handleDeleteModalClose}>
                        Cancel
                    </Button>
                    <Button variant="danger" className="float-right" onClick={this.deleteItem.bind(this, object.id)}>
                        Yes, delete
                    </Button>
                    </div>
                </Modal.Footer>
              </Modal>
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
