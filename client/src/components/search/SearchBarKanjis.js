import React, { Component } from 'react';

class SearchBarKanjis extends Component {
    constructor(props){
        super(props);
        this.state = {
            keyword: "",
            filterType: 20
        };
        
        this.onSubmit = this.onSubmit.bind(this);
        this.handleChange = this.handleChange.bind(this);
    };

    onSubmit(e){
        e.preventDefault();
        
        let data = {
            keyword: this.state.keyword,
            filterType: this.state.filterType
        };

        this.setState({
            keyword: ""
        });

        this.props.fetchQuery(data);
    }
    
    handleChange(e){
        
        this.setState({ [e.target.name]: e.target.value });
    }
    
    render() {

    return (
            <div className="col-lg-12">
                <form onSubmit={this.onSubmit}>
                <div className="row">
                    <div className="col-md-4">
                        <label>Keyword:</label>
                        <input  onChange={this.handleChange}
                                onClick={() => this.setState({ tags: "" })}
                                className="form-control form-control-sm w-75"
                                name="keyword" type="text" placeholder="Search"
                                value={this.state.keyword}
                                aria-label="Search"
                        />
                    </div>
                    <div className="col-md-4">
                        <label>JLPT:</label>
                        <select name="filterType" value={this.state.filterType} className="form-control form-control-sm w-50" onChange={this.handleChange}>
                            <option value="20">All</option>
                            <option value="1">N1</option>
                            <option value="2">N2</option>
                            <option value="3">N3</option>
                            <option value="4">N4</option>
                            <option value="5">N5</option>
                            <option value="-">Uncommon</option>
                        </select>
                    </div>
                    <div className="col-md-4">
                        <button className="btn btn-outline fas fa-search fa-lg brand-button mt-3" aria-hidden="true"></button>
                    </div>
                </div>
                </form>
            </div>
    )};
}

export default SearchBarKanjis;




