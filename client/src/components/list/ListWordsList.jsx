import React, { Component } from "react";
import { Button, Modal } from "react-bootstrap";

class ListWordsList extends Component {
  constructor(props) {
    super(props);
    this.state = {
      showDeleteModal: 0,
    };

    this.handleDeleteModalClose = this.handleDeleteModalClose.bind(this);
  }

  deleteItem(id) {
    this.handleDeleteModalClose();
    this.props.removeFromList(id);
  }

  handleDeleteModalClose() {
    this.setState({ showDeleteModal: 0 });
  }

  openModal(modalId) {
    this.setState({ showDeleteModal: modalId });
  }

  render() {
    let { objects, currentUser, listUserId, editToggle } = this.props;

    const objectList = objects.map((object) => {
      object.meaning = object.meaning.split(",");
      object.meaning = object.meaning.slice(0, 3);
      object.meaning = object.meaning.join(", ");

      return (
        <tr key={object.id}>
          <th scope="row">
            {object.id}
            {currentUser.user.id === listUserId && editToggle ? (
              <button
                className="btn btn-sm btn-danger"
                onClick={this.openModal.bind(this, object.id)}
              >
                -
              </button>
            ) : (
              ""
            )}
          </th>
          <td>{object.word}</td>
          <td>{object.furigana}</td>
          <td>{object.meaning}</td>
          <td>{object.jlpt}</td>
          <td>{object.word_type}</td>
          <Modal
            show={this.state.showDeleteModal === object.id}
            onHide={this.handleDeleteModalClose}
          >
            <Modal.Header closeButton>
              <Modal.Title>Are You Sure? </Modal.Title>
            </Modal.Header>
            <Modal.Footer>
              <div className="col-12">
                <Button
                  variant="secondary"
                  className="float-left"
                  onClick={this.handleDeleteModalClose}
                >
                  Cancel
                </Button>
                <Button
                  variant="danger"
                  className="float-right"
                  onClick={this.deleteItem.bind(this, object.id)}
                >
                  Yes, delete
                </Button>
              </div>
            </Modal.Footer>
          </Modal>
        </tr>
      );
    });

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
        <tbody>{objectList}</tbody>
      </table>
    );
  }
}

export default ListWordsList;
