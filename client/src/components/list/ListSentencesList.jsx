import React, { Component } from "react";
import { Button, Modal } from "react-bootstrap";

class ListSentencesList extends Component {
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
          <td>{object.content}</td>
          <td>
            {object.tatoeba_entry ? (
              <a
                href={`https://tatoeba.org/eng/sentences/show/${object.tatoeba_entry}`}
                target="_blank"
                rel="noopener noreferrer"
              >
                {object.tatoeba_entry}{" "}
                <i className="fas fa-external-link-alt"></i>
              </a>
            ) : (
              "Local"
            )}
          </td>
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
            <th scope="col">Sentence</th>
            <th scope="col">Tatoeba</th>
          </tr>
        </thead>
        <tbody>{objectList}</tbody>
      </table>
    );
  }
}

export default ListSentencesList;
