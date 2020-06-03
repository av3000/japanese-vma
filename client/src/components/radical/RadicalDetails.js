import React, { Component } from 'react';
import axios from 'axios';
import Spinner from '../../assets/images/spinner.gif';
import { Link } from 'react-router-dom';

class RadicalDetails extends Component {
    constructor(props) {
        super(props);
        this.state = {
            radical: {}
        };

        this.addToList = this.addToList.bind(this);
    }

    componentDidMount(){
        let id = this.props.match.params.radical_id;
        axios.get('/api/radical/' + id)
          .then(res => {
            console.log(res);
            this.setState({
              radical: res.data
            });
          })
          .catch(err => {
              console.log(err);
          });
    
      };

      addToList(){
          console.log("radical: " + this.state.radical.id);
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
                            <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i>
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
            </div>
        );
    }
}

export default RadicalDetails
