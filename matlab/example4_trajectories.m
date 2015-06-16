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

% IMPEx-FMI-Matlab usage example 4: Particle trajectories.

% This sample script shows how to plot particle trajectories in the
% magnetic field of a simulation.

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.


%-------------------------------------------------------------------------
% 1. Find the resource ID of a suitable simulation run using
% getMostRelevantRun
%-------------------------------------------------------------------------

% Request the ID of an Earth magnetosphere simulation with average 
% solar wind speed (400 km/s) and negative IMF z (-5 nT):

[id, json] = getMostRelevantRun( 'Earth', ...
    'SW_Utot', 4e5, 'SW_Bz', -5e-9 );

% The json string contains the resourceID of the run and the outputID's of
% the numerical output from the run. In addition, the json string contains
% information on the run parameters, as well as information on how well 
% the requested condition was met. 

% For convenience, the function also extracts the outputID's as a cell
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
% 2. Define the particles and their starting points.
%-------------------------------------------------------------------------

% Let's launch three protons anti-sunward outside the magnetopause and
% two protons earthward in the magnetotail. For that, define... 

% Radius of the Earth:
RE = 6371200;

% Initial positions:
x = [ 10 0 -5; ...
      14 0  0; ...
      10 0  5
      -10 -4 0
      -10  4 0] * RE;

% Initial velocities:
v = [ -4e5 0 0; ...
      -4e5 0 0; ...
      -4e5 0 0
      2e5 0 0
      2e5 0 0];

% Masses:
m = [1 1 1 1 1] * 1.6726e-27;

% Charges:
q = [1 1 1 1 1] * 1.6021773e-19;

% One may only trace one particle species at a time! I.e. masses and
% charges must be equal for all particles.

% Use the dedicated function to compose the particle information into a
% VOTable file:
url = makeParticleVotable( x, v, m, q );


%-------------------------------------------------------------------------
% 3. Get particle trajectories.
%-------------------------------------------------------------------------

% Call getParticleTrajectory to get the trajectories. Mandatory arguments
% are resource ID, url to a particle VOTable, and name of the file where
% resulting data will be saved locally. Optionally one may specify e.g.
% 'stopRadius', which means that particle tracing will be stopped if the
% particle goes inside this radius (a sphere centered at the origin). For
% the Earth simulations with GUMICS, it is advisable to use a stopRadius of
% about 4 RE, as that is the inner boundary of the simulation domain. One
% may define several other parameters, see help getParticleTrajectory.

getParticleTrajectory( idmag, url, 'Trajectories.vot', ...
    'stopRadius', 4*RE, 'maxSteps', 2000 );

% The VOTable format is currently not supported by Matlab. Therefore a
% simple file reading function votread is provided in the IMPEx-FMI-Matlab
% package. Note that it cannot read any VOTable file, only simple files
% such as those made by the IMPEx FMI simulation database service. This can
% be quite slow:

trajectories = votread('Trajectories.vot');


%-------------------------------------------------------------------------
% 4. Rearrange and plot the data.
%-------------------------------------------------------------------------

% The trajectory data is arranged so that all trajectories are combined in 
% the data vectors (e.g. trajectories.X contains the x coordinates of all 
% trajectories), and an additional vector trajectories.Particle_no tells 
% which of the trajectories each datapoint belongs to. Remembering that 
% we asked for 5 particles, the coordinates can be separated into separate 
% trajectories in a cell array e.g. in this way:

particle = cell([1 3]);
npoints = zeros(5, 1);
for i = 1 : length(trajectories.Particle_no)
    n = trajectories.Particle_no(i);
    npoints(n) = npoints(n) + 1;
    particle{n}(npoints(n), 1) = trajectories.X(i);
    particle{n}(npoints(n), 2) = trajectories.Y(i);
    particle{n}(npoints(n), 3) = trajectories.Z(i);
end

% Now we can plot the lines:

figure(1);
clf;
hold on;

plot3( particle{1}(:,1)/RE, particle{1}(:,2)/RE, particle{1}(:,3)/RE, 'b' ); 
plot3( particle{2}(:,1)/RE, particle{2}(:,2)/RE, particle{2}(:,3)/RE, 'b' ); 
plot3( particle{3}(:,1)/RE, particle{3}(:,2)/RE, particle{3}(:,3)/RE, 'b' ); 
plot3( particle{4}(:,1)/RE, particle{4}(:,2)/RE, particle{4}(:,3)/RE, 'r' ); 
plot3( particle{5}(:,1)/RE, particle{5}(:,2)/RE, particle{5}(:,3)/RE, 'r' ); 

box on;
axis equal;
axis([-15 15 -15 15 -25 25]);
sphere;      % This adds a nice planet in the figure.
