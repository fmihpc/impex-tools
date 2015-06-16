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

% IMPEx-FMI-Matlab usage example 3: Magnetic field lines.

% This sample script shows how to plot magnetic field lines.

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.


%-------------------------------------------------------------------------
% 1. Find the resource ID of suitable simulation results.
%-------------------------------------------------------------------------

% Request the ID of a Mars run where the solar wind IMF spiral angle is 
% closest to 45 degrees. The IMF angle is not a preprogrammed search
% parameter, so we define the solar wind function Bx/Btot and say that its
% value should be sin(45deg).

[id, json] = getMostRelevantRun( 'Mars', ...
    'SW_Function', 'SW_Bx / SW_Btot', 'FunctionValue', sind(45) );

% getMostRelevantRun extracts the outputID's as a cell
% array. Out of those, we have to choose the right one to pass as an
% argument to the other functions of this package:
% -- If we want to look at the magnetic field data, we need to choose the 
% ID string that contains the abbreviation 'Mag'. 
% -- For looking at ion data, we need to choose an ID string that contains
% the name of the desired ion species, for example, 'H+' for protons.

% Usually the above said is most easily done just by looking at the
% outputID's and choosing one manually, but for demonstration, we will
% choose them algorithmically:

for i = 1:length(id)
    if ~ isempty( regexpi( id{i}, '/Mag' )) 
        idmag = id{i};  % found the ID for magnetic field data.
    end
end

% Instead of the above, resource ID's can also be found by browsing the
% IMPEx portal at http://impex-portal.oeaw.ac.at


%-------------------------------------------------------------------------
% 2. Choose starting points of the field lines.
%-------------------------------------------------------------------------

% As starting points for the field lines we choose 12 points arranged in 
% a circle above Mars' surface at the noon-midnight meridian:

Rmars = 3386000;

angle = linspace( 0, 360, 13 );
angle = angle(1:12);
coord = [ zeros(12,1), sind(angle'), cosd(angle') ] * 1.3 * Rmars;

% Create a VOTable file containing the coordinates, stored temporarily at 
% an FMI server:

url = makeCoordinateVotable( coord );


%-------------------------------------------------------------------------
% 3. Get field line data.
%-------------------------------------------------------------------------

% Request field line data, tracing to both directions from the starting
% points and saving to Fieldlines.vot:

getFieldLine( idmag, url, 'Fieldlines.vot', 'direction', 'Both' );

% The VOTable format is currently not supported by Matlab. Therefore a
% simple file reading function votread is provided in the IMPEx-FMI-Matlab
% package. Note that it cannot read any VOTable file, only simple files
% such as those made by the IMPEx FMI simulation database service. This can
% be quite slow:

lines = votread('Fieldlines.vot');


%-------------------------------------------------------------------------
% 4. Rearrange the data and plot.
%-------------------------------------------------------------------------

% The field line data is arranged so that all lines are combined in the
% data vectors (e.g. lines.X contains the x coordinates of all points on
% all the lines), and an additional vector lines.Line_no tells which of the
% lines each datapoint belongs to. Remembering that there are 12 lines, 
% the line coordinates can be separated into separate matrices in a cell 
% array e.g. in this way:

line = cell([1 3]);
npoints = zeros(12, 1);
for i = 1 : length(lines.Line_no)
    n = lines.Line_no(i);
    npoints(n) = npoints(n) + 1;
    line{n}(npoints(n), 1) = lines.X(i);
    line{n}(npoints(n), 2) = lines.Y(i);
    line{n}(npoints(n), 3) = lines.Z(i);
end

% We traced the field lines to both directions from the starting point.
% This means that in the resulting sets of points there is a jump when
% tracing to one direction ends and then begins from the starting point to
% the other direction. Assuming that each line end is at least 0.5 Rmars
% from the starting point and that the tracing step was smaller than this,
% the jump can be found in the following way:

for i = 1:12
    jump(i) = find( sum(abs(diff(line{i})), 2) > 0.5*Rmars, 1, 'first' );
end

% Now we can plot the lines:

figure(1);
clf;
hold on;

for i = 1:12
   plot3( line{i}(1:jump(i), 1) / Rmars, ...
       line{i}(1:jump(i), 2) / Rmars, ...
       line{i}(1:jump(i), 3) / Rmars, ...
       'color',[i/12 0 1] ); 
   plot3( line{i}(jump(i)+1 : length(line{i}(:,1)) ,1) / Rmars, ...
       line{i}(jump(i)+1 : length(line{i}(:,1)) ,2) / Rmars, ...
       line{i}(jump(i)+1 : length(line{i}(:,1)) ,3) / Rmars, ...
       'color',[i/12 0 1] );
end

box on;
axis equal;
sphere;     % This adds a nice planet in the figure.
