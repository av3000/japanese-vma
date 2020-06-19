import React, { Component } from 'react';
import axios from 'axios';
import Spinner from '../../assets/images/spinner.gif';
import { Link } from 'react-router-dom';
import { Button, Modal } from 'react-bootstrap';

class RadicalDetails extends Component {
    constructor(props) {
        super(props);
        this.state = {
            radical: {},
            lists: [],
            show: false
        };
        
        this.addToList = this.addToList.bind(this);
        this.removeFromList = this.removeFromList.bind(this);
        this.openModal = this.openModal.bind(this);
        this.handleClose = this.handleClose.bind(this);
        this.getUserRadicalLists = this.getUserRadicalLists.bind(this);
    }

    handleClose(){
        this.setState({show: !this.state.show})
    }

    componentDidMount(){
        let id = this.props.match.params.radical_id;
        axios.get('/api/radical/' + id)
          .then(res => {
            this.setState({
              radical: res.data
            });
          })
          .catch(err => {
              console.log(err);
          });

          if(this.props.currentUser.isAuthenticated){
                this.getUserRadicalLists();
          }
      };

    componentWillReceiveProps(nextProps) {
        if(nextProps.currentUser.isAuthenticated){
            this.getUserRadicalLists();
        }
    }

      getUserRadicalLists(){
        return axios.post(`/api/user/lists/contain`, {
            elementId: this.props.match.params.radical_id
        })
        .then(res => {
            let newState = Object.assign({}, this.state);
            newState.lists = res.data.lists.filter(list => {
                if(list.type === 1 || list.type === 5){
                    return list;
                }
            })

            this.setState( newState );
        })
        .catch(err => {
            console.log(err);
        })
      }

      openModal(){
          if(this.props.currentUser.isAuthenticated === false){
              this.props.history.push('/login');
          }
          else {
              this.setState({show: !this.state.show})
              //   next decision to pick which list to add.
          }
      }

      addToList(id){
            this.setState({show: !this.state.show})
            axios.post("/api/user/list/additemwhileaway", {
                listId: id,
                elementId: this.props.match.params.radical_id
            })
            .then(res => {
                let newState = Object.assign({}, this.state);
                newState.lists.find(list => {
                    if(list.id === id){
                        list.elementBelongsToList = true;
                    }
                });
    
                this.setState( newState );
            })
            .catch(err => console.log(err))
      }

      removeFromList(id){
          this.setState({show: !this.state.show})
          axios.post("/api/user/list/removeitemwhileaway", {
            listId: id,
            elementId: this.props.match.params.radical_id
          })
          .then(res => {
            let newState = Object.assign({}, this.state);
            newState.lists.find(list => {
                if(list.id === id){
                    list.elementBelongsToList = false;
                }
            });

            this.setState( newState );
          })
          .catch(err => console.log(err))
      }

    render() {

        let { radical } = this.state;
        let singleRadical = radical ? (
               <div className="row justify-content-center mt-5">
                <div className="col-md-6">
                            <h1>{radical.radical} <br/>
                            {radical.hiragana}</h1>
                    </div>
                    <div className="col-md-6">
                        <p>
                            meaning: {radical.meaning}, 
                        </p>
                        <p>
                            strokes: {radical.strokes} 
                        </p>
                        <p className="float-right">
                            <i onClick={this.openModal} className="far fa-bookmark ml-3 fa-lg mr-2"></i>
                            {/* <i className="fas fa-external-link-alt fa-lg"></i> */}
                        </p>
                    </div>
               </div>
        ) : (
            <div className="container mt-5">
                <div className="row justify-content-center">
                    <img src={Spinner}/>
                </div>
            </div>
        );

        // Kanjis of radical
        const kanjis = radical.kanjis ? ( radical.kanjis.map(kanji => {

            kanji.meaning = kanji.meaning.split("|")
            kanji.meaning = kanji.meaning.slice(0, 3)
            kanji.meaning = kanji.meaning.join(", ")

            return (
                <div className="row justify-content-center mt-5" key={kanji.id}>
                    <div className="col-md-8">
                        <div className="container">
                        <div className="row justify-content-center">
                            <div className="col-md-6">
                            <h3>{kanji.kanji}</h3>
                            </div>
                            <div className="col-md-4">
                            {kanji.meaning}
                            </div>
                            <div className="col-md-2">
                            <Link to={`/api/kanji/${kanji.id}`} className="float-right">
                                {/* <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i> */}
                                <i className="fas fa-external-link-alt fa-lg"></i>
                            </Link>
                            </div>
                        </div>
                        </div>
                            <hr/>
                    </div>
                    <hr/>
                </div>
            )
        })) : "";

        // Model for radical addint to lists
        let addModal = this.state.lists ? (this.state.lists.map(list => {
            return (
                <div key={list.id}>
                    <div className="col-9"> <Link to={`/list/${list.id}`}>{list.title}</Link>
                        {list.elementBelongsToList ? 
                        (<button className="btn btn-sm btn-danger" onClick={this.removeFromList.bind(this, list.id)}>-</button>)
                        :
                        (<button className="btn btn-sm btn-light" onClick={this.addToList.bind(this, list.id)}>+</button>)
                    }
                     </div>
                </div>
                
            ) })) : ("");
               

        return (
            <div className="container">
                 <span className="mt-4">
                  <Link to="/radicals" className="tag-link">Back</Link>
                </span>
                {singleRadical}
                <hr/>
                { this.state.radical.kanjis ? (
                    <h4>kanjis ({radical.kanjis.length}) results</h4>
                ) : ""}
                {kanjis}
                <Modal show={this.state.show} onHide={this.handleClose}>
                <Modal.Header closeButton>
                    <Modal.Title>Choose List to add</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {addModal}
                    <small> <Link to="/newlist">Want new list?</Link> </small>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={this.handleClose}>
                        Close
                    </Button>
                    {/* <Button variant="primary" onClick={this.handleClose}>
                        Save Changes
                    </Button> */}
                </Modal.Footer>
                </Modal>
            </div>
        );
    }
}

export default RadicalDetails
