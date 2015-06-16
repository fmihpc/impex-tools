% This file is part of the FMI IMPEx tools.
%
% Copyright 2014- Finnish Meteorological Institute
%
% This program is free software: you can redistribute it and/or modify
% it under the terms of the GNU General Public License as published by
% the Free Software Foundation, either version 3 of the License, or
% (at your option) any later version.
%
% This program is distributed in the hope that it will be useful,
% but WITHOUT ANY WARRANTY; without even the implied warranty of
% MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
% GNU General Public License for more details.
%
% You should have received a copy of the GNU General Public License
% along with this program.  If not, see <http://www.gnu.org/licenses/>.

function url = makeCoordinateVotable( X, varargin )
% Creates a VOTable file at the FMI Impex server and returns the URL.
%
% Required arguments:
% X -- a n-by-3 matrix consisting of column vectors, containing a list of
%       (x,y,z) coordinates. The coordinate system must be the one relevant
%       to the simulation run from which data is to be queried using these
%       coordinates. The length unit is metre (m).
%
% Optional Name,Value pairs:
% 'tableName' -- a string to be written as the name of the table.
% 'description' -- a string describing the table.

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.
% Part of the IMPEx-FMI-Matlab package.
% Free software without any warranty.

%========================================================================

% Parse and check input arguments
args = inputParser;
addRequired( args, 'X', @isnumeric );
addParameter( args, 'tableName', '', @ischar );
addParameter( args, 'description', '', @ischar );
parse( args, X, varargin{:} );
if length( X(1,:) ) ~= 3
    error('X must be an N-by-3 matrix.');
end

% Format parameters into strings and compose into a struct
stringX = num2str( X(:,1)' );
stringY = num2str( X(:,2)' );
stringZ = num2str( X(:,3)' );

if ~ strcmp( args.Results.tableName, '' )
    coord.val.tableName.name = 'Table_name';
    coord.val.tableName.val = args.Results.tableName;
    coord.val.description.type = '{http://www.w3.org/2001/XMLSchema}string';
end
if ~ strcmp( args.Results.description, '' )
    coord.val.description.name = 'Description';
    coord.val.description.val = args.Results.description;
    coord.val.description.type = '{http://www.w3.org/2001/XMLSchema}string';
end
coord.val.fieldx.name = 'Fields';
coord.val.fieldx.val.fieldname.name = 'name';
coord.val.fieldx.val.fieldname.val = 'X';
coord.val.fieldx.val.fielddata.name = 'data';
coord.val.fieldx.val.fielddata.val = stringX;
coord.val.fieldy.name = 'Fields';
coord.val.fieldy.val.fieldname.name = 'name';
coord.val.fieldy.val.fieldname.val = 'Y';
coord.val.fieldy.val.fielddata.name = 'data';
coord.val.fieldy.val.fielddata.val = stringY;
coord.val.fieldz.name = 'Fields';
coord.val.fieldz.val.fieldname.name = 'name';
coord.val.fieldz.val.fieldname.val = 'Z';
coord.val.fieldz.val.fielddata.name = 'data';
coord.val.fieldz.val.fielddata.val = stringZ;

% Call the SOAP service
soapMessage = createSoapMessage( ...
    'http://impex-fp7.fmi.fi', ...
    'getVOTableURL', ...
    coord, 'document');
response = callSoapService( ...
    'http://impex-fp7.fmi.fi/ws/Methods_FMI.php', ...
    'getVOTableURL', ...
    soapMessage);

% Parse the SOAP response
i1 = strfind( response, '<ns1:anyURI>' ) + 12;
i2 = strfind( response, '</ns1:anyURI>' ) - 1;
url = response(i1(1) : i2(1));
if ~ strncmp( url, 'http', 4 )
    display( 'Soap error: ' );
    display( url );
    return;
end

return;
